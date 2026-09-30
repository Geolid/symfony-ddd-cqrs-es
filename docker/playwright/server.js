const { chromium } = require('playwright');

(async () => {
    const server = await chromium.launchServer({
        host: '0.0.0.0',
        port: 3000,
        wsPath: 'e2e',
    });

    console.log(`Listening on ${server.wsEndpoint()}`);
})();
