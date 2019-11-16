const cloudscraper = require('cloudscraper');
const FileCookieStore = require('tough-cookie-filestore');
const request = require('request');
const goader = require('./package.json');


require('yargs')
  .scriptName("goader")
  .usage('$0 <cmd> [args]')
  .command('request [method] [cookies] [form-data] url', 'Make the HTTP request with cloudscraper', (yargs) => {
    yargs.positional('method', {
      type: 'string',
      default: 'GET',
      describe: 'The HTTP request method'
    });
    yargs.positional('form-data', {
        type: 'string',
        default: '',
        describe: 'The form data use to submit to the request'
    });
    yargs.positional('headers', {
      type: 'string',
      default: '',
      describe: 'The request header'
  });
    yargs.positional('cookies', {
        type: 'string',
        default: '',
        describe: 'Cookiejar file path'
    });
  }, function (argv) {
    const options = {
        uri: argv.url,
        method: argv.method,
        gzip: true,
        followAllRedirects: true,
    };
    if (argv.formData) {
        options.formData = JSON.parse(argv.formData);
    }
    if (argv.cookies) {
        options.jar = request.jar(new FileCookieStore(argv.cookies));
    }
    if (argv.headers) {
      options.headers = JSON.parse(argv.headers);
    }
    cloudscraper(options).then(console.log).catch(console.error);
  })
  .help()
  .argv