import * as fastify from 'fastify';
export declare class Application {
    private server;
    getServer(): fastify.FastifyInstance;
    bootstrap(address: string, port: number, opts?: fastify.ServerOptionsAsHttp | fastify.ServerOptionsAsSecureHttp | fastify.ServerOptionsAsHttp2 | fastify.ServerOptionsAsSecureHttp2): Promise<any>;
}
