import * as fastify from 'fastify';

export class Application {
  private server: fastify.FastifyInstance;

  getServer(): fastify.FastifyInstance {
    return this.server;
  }

  bootstrap(
    address: string,
    port: number,
    opts?:
      fastify.ServerOptionsAsHttp |
      fastify.ServerOptionsAsSecureHttp |
      fastify.ServerOptionsAsHttp2 |
      fastify.ServerOptionsAsSecureHttp2,
  ): Promise<any> {
    return new Promise<any>((resolve, reject) => {
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
