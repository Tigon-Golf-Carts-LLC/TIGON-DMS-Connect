<?php

namespace Tigon\DmsConnect\Admin;

final class Database_Write_Controller {
    /**
     * Maximum number of rows per batch INSERT/REPLACE query.
     * Keeps individual SQL statements well under MySQL max_allowed_packet.
     */
    private const BATCH_SIZE = 50;

    protected static function write_database_object(Database_Object $database_object) : int {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $posts = $database_object->get_value('posts');

        if(!empty($posts['ID'])) {
            $post_id = $database_object->get_value('posts', 'ID');
            $wpdb->update($wpdb->prefix.'posts', $posts, ['ID' => $post_id]);
        } else {
            $wpdb->insert($wpdb->prefix.'posts', $posts);
            $post_id = $wpdb->insert_id;
        }

        if($post_id) {
            // --- Batch postmeta upsert ---
            self::batch_upsert_postmeta($post_id, $database_object->get_value('postmeta'));

            // --- Batch term_relationships replace ---
            $terms = $database_object->get_value('term_relationships');
            if(count($terms) > 0) {
                $wpdb->delete($wpdb->prefix.'term_relationships', ['object_id' => $post_id]);
                self::batch_insert_terms($post_id, $terms);
            }
        }

        $unique_slug = wp_unique_post_slug(
            $posts['post_name'],
            $posts['ID'],
            $posts['post_status'],
            $posts['post_type'],
            null
        );
        if($unique_slug !== $posts['post_name']) {
            $wpdb->update($wpdb->prefix.'posts', ['post_name' => $unique_slug], ['ID' => $post_id]);
        }

        return $post_id;
    }

    /**
     * Upsert postmeta in batches using a single REPLACE INTO per batch.
     *
     * This replaces the old per-field SELECT + INSERT/UPDATE pattern
     * (which ran 2 queries per meta key = ~160 queries per cart)
     * with batched REPLACE INTO (typically 2-3 queries total per cart).
     */
    private static function batch_upsert_postmeta(int $post_id, array $meta_rows): void {
        global $wpdb;
        $table = $wpdb->prefix . 'postmeta';

        // Filter to only rows that have a value set
        $valid_rows = [];
        foreach ($meta_rows as $meta) {
            if (isset($meta['meta_value'])) {
                $valid_rows[] = $meta;
            }
        }

        if (empty($valid_rows)) {
            return;
        }

        // Collect all meta_keys we need to write
        $meta_keys = array_column($valid_rows, 'meta_key');
        $placeholders = implode(',', array_fill(0, count($meta_keys), '%s'));

        // Fetch existing meta_ids in one query instead of one per key
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $existing = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT meta_id, meta_key FROM {$table} WHERE post_id = %d AND meta_key IN ({$placeholders})",
                array_merge([$post_id], $meta_keys)
            ),
            OBJECT_K // key result set by first column (meta_id) — but we want by meta_key
        );

        // Re-key by meta_key for O(1) lookup
        $existing_by_key = [];
        if ($existing) {
            foreach ($existing as $row) {
                $existing_by_key[$row->meta_key] = $row->meta_id;
            }
        }

        // Split into updates (existing) and inserts (new)
        $to_insert = [];
        $to_update = [];
        foreach ($valid_rows as $meta) {
            $meta['post_id'] = $post_id;
            if (isset($existing_by_key[$meta['meta_key']])) {
                $to_update[] = $meta;
            } else {
                $to_insert[] = $meta;
            }
        }

        // Batch INSERT new meta
        if (!empty($to_insert)) {
            foreach (array_chunk($to_insert, self::BATCH_SIZE) as $chunk) {
                $values = [];
                $value_placeholders = [];
                foreach ($chunk as $meta) {
                    $values[] = $meta['post_id'];
                    $values[] = $meta['meta_key'];
                    $values[] = $meta['meta_value'];
                    $value_placeholders[] = '(%d, %s, %s)';
                }
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $wpdb->query(
                    $wpdb->prepare(
                        "INSERT INTO {$table} (post_id, meta_key, meta_value) VALUES " . implode(', ', $value_placeholders),
                        $values
                    )
                );
            }
        }

        // Batch UPDATE existing meta using CASE statement
        if (!empty($to_update)) {
            foreach (array_chunk($to_update, self::BATCH_SIZE) as $chunk) {
                $case_clauses = [];
                $update_keys = [];
                $params = [];
                foreach ($chunk as $meta) {
                    $meta_id = $existing_by_key[$meta['meta_key']];
                    $case_clauses[] = "WHEN %d THEN %s";
                    $params[] = $meta_id;
                    $params[] = $meta['meta_value'];
                    $update_keys[] = $meta_id;
                }
                $id_placeholders = implode(',', array_fill(0, count($update_keys), '%d'));
                $params = array_merge($params, $update_keys);
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $wpdb->query(
                    $wpdb->prepare(
                        "UPDATE {$table} SET meta_value = CASE meta_id " .
                            implode(' ', $case_clauses) .
                            " END WHERE meta_id IN ({$id_placeholders})",
                        $params
                    )
                );
            }
        }
    }

    /**
     * Insert term_relationships in batches instead of one-at-a-time.
     *
     * Old approach: N individual INSERT queries (one per term).
     * New approach: 1-2 batch INSERT queries for all terms.
     */
    private static function batch_insert_terms(int $post_id, array $terms): void {
        global $wpdb;
        $table = $wpdb->prefix . 'term_relationships';

        foreach (array_chunk($terms, self::BATCH_SIZE) as $chunk) {
            $values = [];
            $value_placeholders = [];
            foreach ($chunk as $term) {
                $term['object_id'] = $post_id;
                $values[] = $term['object_id'];
                $values[] = $term['term_taxonomy_id'];
                $values[] = $term['term_order'] ?? 0;
                $value_placeholders[] = '(%d, %d, %d)';
            }
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->query(
                $wpdb->prepare(
                    "INSERT INTO {$table} (object_id, term_taxonomy_id, term_order) VALUES " . implode(', ', $value_placeholders),
                    $values
                )
            );
        }
    }

    public static function create_from_database_object(Database_Object $database_object) {
        if ( ! empty( $database_object->get_value('posts', 'ID') ) ) {
            return new \WP_Error(
                "tigon_dms_rest_{$database_object->get_value('posts', 'post_type')}_exists",
                sprintf( __( 'Cannot create existing %s.', 'tigon-dms-connect' ), $database_object->get_value('posts', 'post_type') ),
                array( 'status' => 400 )
            );
        }
        $result = self::write_database_object($database_object);

        if($result) {
            return ['pid' => $result, 'onWebsite' => true, 'websiteUrl' => get_permalink($result)];
        }
        return new \WP_Error(
            "tigon_dms_rest_{$database_object->get_value('posts', 'post_type')}_db_write_failure",
            sprintf( __( 'failed to write %s to database.', 'tigon-dms-connect' ), $database_object->get_value('posts', 'post_type') ),
            array( 'status' => 400 )
        );
    }

    public static function update_from_database_object(Database_Object $database_object) {
        if ( ! $database_object->get_value('posts') || 0 == $database_object->get_value('posts', 'ID') ) {
			return new \WP_Error(
                "woocommerce_rest_{$database_object->get_value('posts', 'post_type')}_invalid_id",
                __( 'Invalid ID.', 'woocommerce' ),
                array( 'status' => 400 ) );
		}
        $result = self::write_database_object($database_object);

        if($result) {
            return ['pid' => $result, 'onWebsite' => true, 'websiteUrl' => get_permalink($result)];
        }
        return new \WP_Error(
            "tigon_dms_rest_{$database_object->get_value('posts', 'post_type')}_db_write_failure",
            sprintf( __( 'failed to write %s to database.', 'tigon-dms-connect' ), $database_object->get_value('posts', 'post_type') ),
            array( 'status' => 400 )
        );
    }

    public static function delete_by_id($request) {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		$id    = (int) $request->get_value('posts','ID');
		$post  = get_post( $id );

        if ( empty( $id ) || empty( $post->ID )) {
			return new \WP_Error(
                "tigon_dms_rest_invalid_id",
                __( 'ID is invalid.', 'tigon-dms-connect' ),
                array( 'status' => 404 )
            );
		}

        $posts = $wpdb->delete($wpdb->prefix.'posts', ['ID' => $id]);
        $postmeta = $wpdb->delete($wpdb->prefix.'postmeta', ['post_id' => $id]);
        $terms = $wpdb->delete($wpdb->prefix.'term_relationships', ['object_id' => $id]);
        $wcds = $wpdb->delete($wpdb->prefix.'wc_product_meta_lookup', ['product_id' => $id]);

        return ['post' => $id, 'onWebsite' => false];
    }
}
