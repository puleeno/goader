const cloudscraper = require('cloudscraper');
const FileCookieStore = require('tough-cookie-filestore');
const request = require('request');
const goader = require('./package.json');
const Buffer = require('buffer');
const fs = require('fs');

require('yargs')
  .scriptName("goader")
  .usage('$0 <cmd> [args]')
  .command('request [method] [cookies] [form-data] url', 'Make the HTTP request with cloudscraper', (yargs) => {
    yargs.positional('method', {
      type: 'string',
      default: 'GET',
      describe: 'The HTTP request method'
    });
    yargs.positional('formdata', {
        type: 'string',
        default: '',
        describe: 'The form data use to submit to the request'
    });
    yargs.positional('saveto', {
      type: 'string',
      default: '',
      describe: 'Write the response to file'
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
    if (argv.formdata) {
        options.formData = JSON.parse(argv.formdata);
    }
    if (argv.cookies) {
        options.jar = request.jar(new FileCookieStore(argv.cookies));
    }
    if (argv.headers) {
      options.headers = JSON.parse(argv.headers);
    }
    cloudscraper(options).then((response) => {
      if (argv.saveto) {
        const buff = Buffer.from(response, 'utf8');
        fs.writeFileSync(argv.saveto, buff);
      } else {
        console.log(response);
      }
    }).catch(console.log);
  })
  .help()
  .argv