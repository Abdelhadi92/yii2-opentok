
yii2-opentok
=========
Yii2 client for opentok (http://www.tokbox.com/opentok)


## Installation

1. Run the [Composer](http://getcomposer.org/download/) command to install the latest version:

    ```bash
    composer require abushamleh/yii2-opentok "dev-master"
    ```
2. Add the component to `config/main.php`

    ```php
    'components' => [
        // ...
        'opentok' => [
            'class'      => 'abushamleh\opentok\Client',
            'api_key'    => 'your api key',
            'api_secret' => 'your secret key',
        ],
        // ...
    ],
    ```
### Creating Sessions

To create an OpenTok Session, use the `createSession($options)` method of the
`OpenTok\OpenTok` class. The `$options` parameter is an optional array used to specify the following:

* Setting whether the session will use the OpenTok Media Router or attempt to send streams directly
  between clients.

* Setting whether the session will automatically create archives (implies use of routed session)

* Specifying a location hint.

The `getSessionId()` method of the `OpenTok\Session` instance returns the session ID,
which you use to identify the session in the OpenTok client libraries.

```php
use OpenTok\MediaMode;
use OpenTok\ArchiveMode;

// Create a session that attempts to use peer-to-peer streaming:
$session = Yii::$app->opentok->createSession();

// A session that uses the OpenTok Media Router, which is required for archiving:
$session = Yii::$app->opentok->createSession(array( 'mediaMode' => MediaMode::ROUTED ));

// A session with a location hint:
$session = Yii::$app->opentok->createSession(array( 'location' => '12.34.56.78' ));

// An automatically archived session:
$sessionOptions = array(
    'archiveMode' => ArchiveMode::ALWAYS,
    'mediaMode' => MediaMode::ROUTED
);
$session = Yii::$app->opentok->createSession($sessionOptions);


// Store this sessionId in the database for later use
$sessionId = $session->getSessionId();
```

### Generating Tokens

Once a Session is created, you can start generating Tokens for clients to use when connecting to it.
You can generate a token either by calling the `generateToken($sessionId, $options)` method of the
`OpenTok\OpenTok` class, or by calling the `generateToken($options)` method on the `OpenTok\Session`
instance after creating it. The `$options` parameter is an optional array used to set the role,
expire time, and connection data of the Token. For layout control in archives and broadcasts, 
the initial layout class list of streams published from connections using this token can be set as well.

```php
use OpenTok\Session;
use OpenTok\Role;

// Generate a Token from just a sessionId (fetched from a database)
$token = Yii::$app->opentok->generateToken($sessionId);
// Generate a Token by calling the method on the Session (returned from createSession)
$token = $session->generateToken();

// Set some options in a token
$token = $session->generateToken(array(
    'role'       => Role::MODERATOR,
    'expireTime' => time()+(7 * 24 * 60 * 60), // in one week
    'data'       => 'name=Johnny',
    'initialLayoutClassList' => array('focus')
));
```

### Working with Streams

You can get information about a stream by calling the `getStream($sessionId, $streamId)` method of the
`OpenTok\OpenTok` class.

```php
use OpenTok\Session;

// Get stream info from just a sessionId (fetched from a database)
$stream = Yii::$app->opentok->getStream($sessionId, $streamId);

// Stream properties
$stream->id; // string with the stream ID
$stream->videoType; // string with the video type
$stream->name; // string with the name
$stream->layoutClassList; // array with the layout class list
```

You can get information about all the streams in a session by calling the `listStreams($sessionId)` method of the
`OpenTok\OpenTok` class.

```php
use OpenTok\Session;

// Get list of streams from just a sessionId (fetched from a database)
$streamList = Yii::$app->opentok->listStreams($sessionId);

$streamList->totalCount(); // total count
```

### Working with Archives

You can only archive sessions that use the OpenTok Media Router
(sessions with the media mode set to routed).

You can start the recording of an OpenTok Session using the `startArchive($sessionId, $name)` method
of the `OpenTok\OpenTok` class. This will return an `OpenTok\Archive` instance. The parameter
`$archiveOptions` is an optional array and is used to assign a name, whether to record audio and/or
video, the desired output mode for the Archive, and the desired resolution if applicable. Note that you can only start an
Archive on a Session that has clients connected.

```php
// Create a simple archive of a session
$archive = Yii::$app->opentok->startArchive($sessionId);


// Create an archive using custom options
$archiveOptions = array(
    'name' => 'Important Presentation',     // default: null
    'hasAudio' => true,                     // default: true
    'hasVideo' => true,                     // default: true
    'outputMode' => OutputMode::COMPOSED,   // default: OutputMode::COMPOSED
    'resolution' => '1280x720'              // default: '640x480'
);
$archive = Yii::$app->opentok->startArchive($sessionId, $archiveOptions);

// Store this archiveId in the database for later use
$archiveId = $archive->id;
```

If you set the `outputMode` option to `OutputMode::INDIVIDUAL`, it causes each stream in the archive to be recorded to its own individual file. Please note that you cannot specify the resolution when you set the `outputMode` option to `OutputMode::INDIVIDUAL`. The `OutputMode::COMPOSED` setting (the default) causes all streams in the archive to be recorded to a single (composed) file.

Note that you can also create an automatically archived session, by passing in `ArchiveMode::ALWAYS`
as the `archiveMode` key of the `options` parameter passed into the `OpenTok->createSession()`
method (see "Creating Sessions," above).

You can stop the recording of a started archive using the `stopArchive($archiveId)` method of the
`OpenTok\OpenTok` object. You can also do this using the `stop()` method of the
`OpenTok\Archive` instance.

```php
// Stop an Archive from an archiveId (fetched from database)
Yii::$app->opentok->stopArchive($archiveId);
// Stop an Archive from an Archive instance (returned from startArchive)
$archive->stop();
```

To get an `OpenTok\Archive` instance (and all the information about it) from an archive ID, use the
`getArchive($archiveId)` method of the `OpenTok\OpenTok` class.

```php
$archive = Yii::$app->opentok->getArchive($archiveId);
```

To delete an Archive, you can call the `deleteArchive($archiveId)` method of the `OpenTok\OpenTok`
class or the `delete()` method of an `OpenTok\Archive` instance.

```php
// Delete an Archive from an archiveId (fetched from database)
Yii::$app->opentok->deleteArchive($archiveId);
// Delete an Archive from an Archive instance (returned from startArchive, getArchive)
$archive->delete();
```

You can also get a list of all the Archives you've created (up to 1000) with your API Key. This is
done using the `listArchives($offset, $count)` method of the `OpenTok/OpenTok` class. The parameters
`$offset` and `$count` are optional and can help you paginate through the results. This will return
an instance of the `OpenTok\ArchiveList` class.

```php
$archiveList = Yii::$app->opentok->listArchives();

// Get an array of OpenTok\Archive instances
$archives = $archiveList->getItems();
// Get the total number of Archives for this API Key
$totalCount = $archiveList->totalCount();
```

For composed archives, you can change the layout dynamically, using the `updateArchiveLayout($archiveId, $layoutType)` method:

```php
use OpenTok\OpenTok;

$layout Layout::getPIP(); // Or use another get method of the Layout class.
Yii::$app->opentok->updateArchiveLayout($archiveId, $layout);
```

You can set the initial layout class for a client's streams by setting the `layout` option when
you create the token for the client, using the `OpenTok->generateToken()` method or the
`Session->generateToken()` method. And you can change the layout classes for a stream
by calling the `OpenTok->updateStream()` method.

Setting the layout of composed archives is optional. By default, composed archives use the
"best fit" layout (see [Customizing the video layout for composed
archives](https://tokbox.com/developer/guides/archiving/layout-control.html)).

For more information on archiving, see the
[OpenTok archiving](https://tokbox.com/developer/guides/archiving/) developer guide.

### Working with Broadcasts

You can only start live streaming broadcasts for sessions that use the OpenTok Media Router
(sessions with the media mode set to routed).

Start the live streaming broadcast of an OpenTok Session using the
`startBroadcast($sessionId, $options)` method of the `OpenTok\OpenTok` class.
This will return an `OpenTok\Broadcast` instance. The `$options` parameter is
an optional array used to assign a layout type for the broadcast.

```php
// Start a live streaming broadcast of a session
$broadcast = Yii::$app->opentok->startBroadcast($sessionId);


// Start a live streaming broadcast of a session, setting a layout type
$options = array(
    'layout' => Layout::getBestFit()
);
$broadcast = Yii::$app->opentok->startBroadcast($sessionId, $options);

// Store the broadcast ID in the database for later use
$broadcastId = $broadcast->id;
```

You can stop the live streaming broadcast using the `stopBroadcast($broadcastId)` method of the
`OpenTok\OpenTok` object. You can also do this using the `stop()` method of the
`OpenTok\Broadcast` instance.

```php
// Stop a broadcast from an broadcast ID (fetched from database)
Yii::$app->opentok->stopBroadcast($broadcastId);

// Stop a broadcast from an Broadcast instance (returned from startBroadcast)
$broadcast->stop();
```

To get an `OpenTok\Broadcast` instance (and all the information about it) from a broadcast ID,
use the `getBroadcast($broadcastId)` method of the `OpenTok\OpenTok` class.

```php
$broadcast = Yii::$app->opentok->getBroadcast($broadcastId);
```

You can set change the layout dynamically, using the
`OpenTok->updateBroadcastLayout($broadcastId, $layout)` method:

```php
use OpenTok\OpenTok;

$layout Layout::getPIP(); // Or use another get method of the Layout class.
Yii::$app->opentok->updateBroadcastLayout($broadcastId, $layout);
```
You can use the `Layout` class to set the layout types:
`Layout::getHorizontalPresentation()`, `Layout::getVerticalPresentation()`,  `Layout::getPIP()`,
`Layout::getBestFit()`, `Layout::createCustom()`.

```php
$layoutType = Layout::getHorizontalPresentation();
Yii::$app->opentok->setArchiveLayout($archiveId, $layoutType);

// For custom Layouts, you can do the following
$options = array(
    'stylesheet' => 'stream.instructor {position: absolute; width: 100%;  height:50%;}'
);

$layoutType = Layout::createCustom($options);
Yii::$app->opentok->setArchiveLayout($archiveId, $layoutType);
```

You can set the initial layout class for a client's streams by setting the `layout` option when
you create the token for the client, using the `OpenTok->generateToken()` method or the
`Session->generateToken()` method. And you can change the layout classes for a stream
by calling the `Yii::$app->opentok->updateStream()` method.

Setting the layout of live streaming broadcasts is optional. By default, broadcasts use the
"best fit" layout (see [Configuring video layout for OpenTok live streaming
broadcasts](https://tokbox.com/developer/guides/broadcast/live-streaming/#configuring-video-layout-for-opentok-live-streaming-broadcasts)).

For more information on live streaming broadcasts, see the
[OpenTok live streaming broadcasts](https://tokbox.com/developer/guides/broadcast/live-streaming/)
developer guide.

### Force a Client to Disconnect

Your application server can disconnect a client from an OpenTok session by calling the `forceDisconnect($sessionId, $connectionId)` 
method of the `OpenTok\OpenTok` class.

```php
use OpenTok\OpenTok;

// Force disconnect a client connection
Yii::$app->opentok->forceDisconnect($sessionId, $connectionId);
```
### Sending Signals

Once a Session is created, you can send signals to everyone in the session or to a specific connection.
You can send a signal by calling the `signal($sessionId, $payload, $connectionId)` method of the
`OpenTok\OpenTok` class.

The `$sessionId` parameter is the session ID of the session.

The `$payload` parameter is an associative array used to set the
following:

* `data` (string) -- The data string for the signal. You can send a maximum of 8kB.

* `type` (string) -- &mdash; (Optional) The type string for the signal. You can send a maximum of 128 characters, and only the following characters are allowed: A-Z, a-z, numbers (0-9), '-', '_', and '~'.

The `$connectionId` parameter is an optional string used to specify the connection ID of
a client connected to the session. If you specify this value, the signal is sent to
the specified client. Otherwise, the signal is sent to all clients connected to the session.


```php
use OpenTok\OpenTok;

// Send a signal to a specific client
$signalPayload = array(
    'data' => 'some signal message',
    'type' => 'signal type'
);
$connectionId = 'da9cb410-e29b-4c2d-ab9e-fe65bf83fcaf';
Yii::$app->opentok->signal($sessionId, $signalPayload, $connectionId);

// Send a signal to everyone in the session
$signalPayload = array(
    'data' => 'some signal message',
    'type' => 'signal type'
);
Yii::$app->opentok->signal($sessionId, $signalPayload);
```

For more information, see the [OpenTok signaling developer
guide](https://tokbox.com/developer/guides/signaling/).

## Working with SIP Interconnect

You can add an audio-only stream from an external third-party SIP gateway using the SIP
Interconnect feature. This requires a SIP URI, the session ID you wish to add the audio-only
stream to, and a token to connect to that session ID.

To initiate a SIP call, call the `dial($sessionId, $token, $sipUri, $options)` method of the
`OpenTok\OpenTok` class:

```php
$sipUri = 'sip:user@sip.partner.com;transport=tls';

$options = array(
  'headers' =>  array(
    'X-CUSTOM-HEADER' => 'headerValue'
  ),
  'auth' => array(
    'username' => 'username',
    'password' => 'password'
  ),
  'secure' => true,
  'from' => 'from@example.com'
);

Yii::$app->opentok->dial($sessionId, $token, $sipUri, $options);
```

For more information, see the [OpenTok SIP Interconnect developer
guide](https://tokbox.com/developer/guides/sip/).

## Force Disconnect

Your application server can disconnect a client from an OpenTok session by calling the `forceDisconnect($sessionId, $connectionId)` 
method of the `OpenTok\OpenTok` class.

```php
use OpenTok\OpenTok;

// Force disconnect a client connection
Yii::$app->opentok->forceDisconnect($sessionId, $connectionId);
```
## Sending Signals

Once a Session is created, you can send signals to everyone in the session or to a specific connection.
You can send a signal by calling the `signal($sessionId, $payload, $connectionId)` method of the
`OpenTok\OpenTok` class.

The `$sessionId` parameter is the session ID of the session.

The `$payload` parameter is an associative array used to set the
following:

* `data` (string) -- The data string for the signal. You can send a maximum of 8kB.

* `type` (string) -- &mdash; (Optional) The type string for the signal. You can send a maximum of 128 characters, and only the following characters are allowed: A-Z, a-z, numbers (0-9), '-', '_', and '~'.

The `$connectionId` parameter is an optional string used to specify the connection ID of
a client connected to the session. If you specify this value, the signal is sent to
the specified client. Otherwise, the signal is sent to all clients connected to the session.


```php
use OpenTok\OpenTok;

// Send a signal to a specific client
$signalPayload = array(
    'data' => 'some signal message',
    'type' => 'signal type'
);
$connectionId = 'da9cb410-e29b-4c2d-ab9e-fe65bf83fcaf';
Yii::$app->opentok->signal($sessionId, $signalPayload, $connectionId);

// Send a signal to everyone in the session
$signalPayload = array(
    'data' => 'some signal message',
    'type' => 'signal type'
);
Yii::$app->opentok->signal($sessionId, $signalPayload);
```

For more information, see the [OpenTok signaling developer
guide](https://tokbox.com/developer/guides/signaling/).

## Samples

There are three sample applications included in this repository. To get going as fast as possible, clone the whole
repository and follow the Walkthroughs:

*  [HelloWorld](sample/HelloWorld/README.md)
*  [Archiving](sample/Archiving/README.md)
*  [SipCall](sample/SipCall/README.md)

## Documentation

Reference documentation is available at
<https://tokbox.com/developer/sdks/php/reference/index.html>.



