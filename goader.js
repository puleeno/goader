const program = require('commander');
const cloudscraper = require('cloudscraper');
const goader = require('./package.json');

program
    .version(goader.version)
    .description(goader.description)
    .command('*')
    .option('-m', '--method', 'Request method')
    .action(function(env){
        const options = {};
        options.uri = env;
        cloudscraper.get(options).then(console.log).catch(console.error);
    });

program.parse(process.argv);
