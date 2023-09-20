/**
 * Welcome to Cloudflare Workers! This is your first worker.
 *
 * - Run `wrangler dev src/index.ts` in your terminal to start a development server
 * - Open a browser tab at http://localhost:8787/ to see your worker in action
 * - Run `wrangler deploy src/index.ts --name my-worker` to deploy your worker
 *
 * Learn more at https://developers.cloudflare.com/workers/
 */

import type { Request, Response } from '@cloudflare/workers-types'
import type Env from './types/Env'

import CloudflareImageResizingHandler from './CloudflareImageResizingHandler'

export default {
  async fetch(request: Request, env: Env): Promise<Response> | Response {
    const handler = new CloudflareImageResizingHandler(env)

    return handler.process(request)
  },
}
