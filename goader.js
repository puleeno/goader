const cloudscraper = require('cloudscraper');
const FileCookieStore = require('tough-cookie-filestore');
const program = require('commander');
const request = require('request');
const goader = require('./package.json');

program
    .version(goader.version)
    .description(goader.description)
    .command('*')
    .option('-m', '--method', 'Request method')
    .action(function(env){
        const j = request.jar(new FileCookieStore('cookiejar.json'));
        const options = {
            jar: j,
            uri: env,
            method: 'GET',
            gzip: true,
        };
        options.formData = {};
        cloudscraper(options).then(console.log).catch(console.error);
    });

program.parse(process.argv);
