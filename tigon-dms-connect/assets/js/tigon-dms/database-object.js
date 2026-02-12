export class Database_Object {
    constructor({data}) {
        this.data = data;
    }

    unserialize(rawData) {
        const { data } = JSON.parse(rawData);
        this.data = data;
    }
}