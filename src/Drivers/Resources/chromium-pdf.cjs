const fs = require('fs');

async function generatePdf() {
    const configPath = process.argv[2];
    if (!configPath || !fs.existsSync(configPath)) {
        console.error('Config file missing or invalid');
        process.exit(1);
    }

    const config = JSON.parse(fs.readFileSync(configPath, 'utf8'));

    let puppeteer;
    try {
        puppeteer = require('puppeteer');
    } catch (e) {
        try {
            puppeteer = require('puppeteer-core');
        } catch (err) {
            console.error('Puppeteer is not installed. Run "npm install puppeteer" or "npm install -g puppeteer"');
            process.exit(2);
        }
    }

    const launchArgs = ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage', '--disable-gpu'];
    const launchOptions = {
        headless: 'new',
        args: launchArgs
    };

    if (config.executablePath) {
        launchOptions.executablePath = config.executablePath;
    }

    const browser = await puppeteer.launch(launchOptions);
    try {
        const page = await browser.newPage();

        if (config.mediaType) {
            await page.emulateMediaType(config.mediaType);
        }

        if (config.url) {
            await page.goto(config.url, { waitUntil: 'networkidle0', timeout: (config.timeout || 30) * 1000 });
        } else if (config.html) {
            await page.setContent(config.html, { waitUntil: 'networkidle0', timeout: (config.timeout || 30) * 1000 });
        }

        if (config.waitForSelector) {
            await page.waitForSelector(config.waitForSelector, { timeout: (config.timeout || 30) * 1000 });
        }

        if (config.waitForTimeout) {
            await new Promise(resolve => setTimeout(resolve, config.waitForTimeout));
        }

        const pdfOptions = {
            path: config.outputPath,
            format: config.format || 'A4',
            landscape: !!config.landscape,
            printBackground: config.printBackground !== false,
            scale: config.scale || 1.0,
            margin: config.margin || { top: '10mm', right: '10mm', bottom: '10mm', left: '10mm' },
            displayHeaderFooter: !!(config.headerTemplate || config.footerTemplate),
            headerTemplate: config.headerTemplate || '<span></span>',
            footerTemplate: config.footerTemplate || '<span></span>'
        };

        await page.pdf(pdfOptions);
    } finally {
        await browser.close();
    }
}

generatePdf().catch(err => {
    console.error(err.stack || err.message);
    process.exit(1);
});
