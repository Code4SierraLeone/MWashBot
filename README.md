MWashBot
============

This telegram bot is an extension of the [MWash](https://github.com/Code4SierraLeone/MWash) website.

### Configuration

Get the Bot Token from Telegrams's [BotFather](https://telegram.me/BotFather)

Add the token here on line 3
```
define('BOT_TOKEN', '');
```

Update the database configuration on line 128
 
```
$hostname = ''; $dbname = ''; $username = ''; $password = '';
```

Add the host URL on line 275

```
define('WEBHOOK_URL', '');
```

Set the Webhook URL by sending a http request on your browser or terminal

```
https://api.telegram.org/[bot_token]/setWebhook?url=https://www.example.com
```

Run the `composer install` command in the root of the cloned project's directory. This command will download and install the dependencies.


