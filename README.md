# Here's a challenge for you: Can you create a script that can beat this hangman API?

The API is very simple: To start a new game of hangman, simply visit https://hangman.timothyoesch.ch/new

This will provide you with a response that looks something like this:

```json
{
	"status": 201,
	"msg": "game_123456789 successfully created!",
	"game_ID": "game_123456789",
	"url": "https://hangman.timothyoesch.ch/play/?id=game_123456789",
	"clue": "*************"
}
```

The "game_ID" is what you will need to play the game. The "clue" is what you can give to your script. To see the game stats, simply visit the URL that is provided to you.



## Guess a letter

To guess a letter, perform a POST Request to this base URL https://hangman.timothyoesch.ch/play/?id=XXGAME_IDXX&type=letter. Make sure to replace XXGAME_IDXX with the game_ID that was provided to you. Also make sure to send your guess as JSON and therefore set the content-type to application/json. You will have to provide a "userkey": To receive such a key, please [write me an email](mailto:timothy@kpunkt.ch). The userkey is used to monitor win/loss statistics. Once the challenge has taken off, these statistics will be published. 

If you want to guess the letter "a" for example, the request should look somethink like this (using cURL):

```cURL
curl "http://https://hangman.timothyoesch.ch/play/?id=game_60d1f1eac7d83&type=letter" \
  -X POST \
  -d "{\r\n  \"userkey\": \"USERKEY\",\r\n  \"letter\": \"a\"\r\n}" \
  -H "content-type: application/json" 
```

The response will look something like this:

```json
{
  "status": "correctletter",
  "msg": "You guessed a correct letter!",
  "clue": "*****a****",
  "strikes": 0,
  "guessed": [
    "a"
  ],
  "gamestate": "Open"
}
```

"status" can either be "correctletter", "strike" or "lost" for letter guesses. The "clue" will change as you guess letters. "guessed" will provide you with the letters you have previously guessed. Repeated guesses (eventho they don't make sense) will be shown repeatedly and do not count as a strike. If you receive more than 5 strikes, you loose the game.



## Guess a word

If you feel comfortable to guess a word, make a POST Request to this base URL https://hangman.timothyoesch.ch/play/?id=XXGAME_IDXX&type=letter. Make sure to replace XXGAME_IDXX with the game_ID that was provided to you. Also make sure to send your guess as JSON and therefore set the content-type to application/json. You will have to provide the same "userkey" here as you did when guessing a letter. If you want to guess the word "socialism" for example, the request should look somethink like this (using cURL):

```cURL
curl "https://hangman.timothyoesch.ch/play/?id=game_60d1f1eac7d83&type=word" \
  -X POST \
  -d "{\r\n  \"userkey\": \"USERKEY\",\r\n  \"word\": \"socialism\"\r\n}" \
  -H "content-type: application/json" 
```



Once you guessed a word there's only two possible outcomes: You either guessed the right word, in which case you have won, or you guessed the wrong word, in which case you have lost. The response will look similar to the one where you guessed a word, with the exception that status can now only be "won" or "lost". If you won, the clue will tell you the correct word.



**Have fun!**