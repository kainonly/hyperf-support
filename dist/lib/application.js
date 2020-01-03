"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const fastify = require("fastify");
class Application {
    getServer() {
        return this.server;
    }
    bootstrap(address, port, opts) {
        return new Promise((resolve, reject) => {
            this.server = fastify(opts);
            this.server.listen(port, address, (err, address) => {
                if (err) {
                    reject(err);
                }
                resolve(address);
            });
        });
    }
}
exports.Application = Application;
