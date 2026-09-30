# Must match the npm "playwright" version vendor/playwright-php/playwright's own bundled
# Node relay installs (check vendor/playwright-php/playwright/bin/node_modules/playwright/package.json
# after a `composer update`) — the wire protocol rejects a client/server version mismatch.
FROM mcr.microsoft.com/playwright:v1.63.0-noble

WORKDIR /srv

COPY package.json .
RUN npm install

COPY server.js .

CMD ["node", "server.js"]
