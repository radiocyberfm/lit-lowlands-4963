<?php
require_once('stream.php');
/**
 * This sample app is provided to kickstart your experience using Facebook's
 * resources for developers.  This sample app provides examples of several
 * key concepts, including authentication, the Graph API, and FQL (Facebook
 * Query Language). Please visit the docs at 'developers.facebook.com/docs'
 * to learn more about the resources available to you
 */

// Provides access to app specific values such as your app id and app secret.
// Defined in 'AppInfo.php'
require_once('AppInfo.php');

// Enforce https on production
if (substr(AppInfo::getUrl(), 0, 8) != 'https://' && $_SERVER['REMOTE_ADDR'] != '127.0.0.1') {
  header('Location: https://'. $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
  exit();
}

// This provides access to helper functions defined in 'utils.php'
require_once('utils.php');


/*****************************************************************************
 *
 * The content below provides examples of how to fetch Facebook data using the
 * Graph API and FQL.  It uses the helper functions defined in 'utils.php' to
 * do so.  You should change this section so that it prepares all of the
 * information that you want to display to the user.
 *
 ****************************************************************************/

require_once('sdk/src/facebook.php');

$facebook = new Facebook(array(
  'appId'  => AppInfo::appID(),
  'secret' => AppInfo::appSecret(),
  'sharedSession' => true,
  'trustForwarded' => true,
));

$user_id = $facebook->getUser();
if ($user_id) {
  try {
    // Fetch the viewer's basic information
    $basic = $facebook->api('/me');
  } catch (FacebookApiException $e) {
    // If the call fails we check if we still have a user. The user will be
    // cleared if the error is because of an invalid accesstoken
    if (!$facebook->getUser()) {
      header('Location: '. AppInfo::getUrl($_SERVER['REQUEST_URI']));
      exit();
    }
  }

  // This fetches some things that you like . 'limit=*" only returns * values.
  // To see the format of the data you are retrieving, use the "Graph API
  // Explorer" which is at https://developers.facebook.com/tools/explorer/
  $likes = idx($facebook->api('/me/likes?limit=4'), 'data', array());

  // This fetches 4 of your friends.
  $friends = idx($facebook->api('/me/friends?limit=4'), 'data', array());

  // And this returns 16 of your photos.
  $photos = idx($facebook->api('/me/photos?limit=16'), 'data', array());

  // Here is an example of a FQL call that fetches all of your friends that are
  // using this app
  $app_using_friends = $facebook->api(array(
    'method' => 'fql.query',
    'query' => 'SELECT uid, name FROM user WHERE uid IN(SELECT uid2 FROM friend WHERE uid1 = me()) AND is_app_user = 1'
  ));
}

// Fetch the basic info of the app that they are using
$app_info = $facebook->api('/'. AppInfo::appID());

$app_name = idx($app_info, 'name', '');

?>
<!DOCTYPE html>
<html xmlns:fb="http://ogp.me/ns/fb#" lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=2.0, user-scalable=yes" />

    <title><?php echo he($app_name); ?></title>
    <link rel="stylesheet" href="stylesheets/screen.css" media="Screen" type="text/css" />
    <link rel="stylesheet" href="stylesheets/mobile.css" media="handheld, only screen and (max-width: 480px), only screen and (max-device-width: 480px)" type="text/css" />

    <!--[if IEMobile]>
    <link rel="stylesheet" href="mobile.css" media="screen" type="text/css"  />
    <![endif]-->

    <!-- These are Open Graph tags.  They add meta data to your  -->
    <!-- site that facebook uses when your content is shared     -->
    <!-- over facebook.  You should fill these tags in with      -->
    <!-- your data.  To learn more about Open Graph, visit       -->
    <!-- 'https://developers.facebook.com/docs/opengraph/'       -->
    <meta property="og:title" content="<?php echo he($app_name); ?>" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="www.radiocyberfm.org" />
    <meta property="og:image" content="<?php echo AppInfo::getUrl('/logo.png'); ?>" />
    <meta property="og:site_name" content="<?php echo he($app_name); ?>" />
    <meta property="og:description" content="RadioCyber FM On Facebook" />
    <meta property="fb:app_id" content="<?php echo AppInfo::appID(); ?>" />

    <script type="text/javascript" src="/javascript/jquery-1.7.1.min.js"></script>

    <script type="text/javascript">
      function logResponse(response) {
        if (console && console.log) {
          console.log('The response was', response);
        }
      }

      $(function(){
        // Set up so we handle click on the buttons
        $('#postToWall').click(function() {
          FB.ui(
            {
              method : 'feed',
              link   : $(this).attr('data-url')
            },
            function (response) {
              // If response is null the user canceled the dialog
              if (response != null) {
                logResponse(response);
              }
            }
          );
        });

        $('#sendToFriends').click(function() {
          FB.ui(
            {
              method : 'send',
              link   : $(this).attr('data-url')
            },
            function (response) {
              // If response is null the user canceled the dialog
              if (response != null) {
                logResponse(response);
              }
            }
          );
        });

        $('#sendRequest').click(function() {
          FB.ui(
            {
              method  : 'apprequests',
              message : $(this).attr('data-message')
            },
            function (response) {
              // If response is null the user canceled the dialog
              if (response != null) {
                logResponse(response);
              }
            }
          );
        });
      });
    </script>

    <!--[if IE]>
      <script type="text/javascript">
        var tags = ['header', 'section'];
        while(tags.length)
          document.createElement(tags.pop());
      </script>
    <![endif]-->
  </head>
  <body>
    <div id="fb-root"></div>
    <script type="text/javascript">
      window.fbAsyncInit = function() {
        FB.init({
          appId      : '<?php echo AppInfo::appID(); ?>', // App ID
          channelUrl : '//<?php echo $_SERVER["HTTP_HOST"]; ?>/channel.html', // Channel File
          status     : true, // check login status
          cookie     : true, // enable cookies to allow the server to access the session
          xfbml      : true // parse XFBML
        });

        // Listen to the auth.login which will be called when the user logs in
        // using the Login button
        FB.Event.subscribe('auth.login', function(response) {
          // We want to reload the page now so PHP can read the cookie that the
          // Javascript SDK sat. But we don't want to use
          // window.location.reload() because if this is in a canvas there was a
          // post made to this page and a reload will trigger a message to the
          // user asking if they want to send data again.
          window.location = window.location;
        });

        FB.Canvas.setAutoGrow();
      };

      // Load the SDK Asynchronously
      (function(d, s, id) {
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) return;
        js = d.createElement(s); js.id = id;
        js.src = "//connect.facebook.net/en_US/all.js";
        fjs.parentNode.insertBefore(js, fjs);
      }(document, 'script', 'facebook-jssdk'));
    </script>

    <header class="clearfix">
	
	<img src="/images/logo.png">
			
      <?php if (isset($basic)) { ?>
      <p id="picture" style="background-image: url(https://graph.facebook.com/<?php echo he($user_id); ?>/picture?type=normal)"></p>
      <div>
        <h1>Selamat datang, <strong><?php echo he(idx($basic, 'name')); ?></strong></h1>
        <p class="tagline">
        Applikasi <a href="<?php echo he(idx($app_info, 'link'));?>" target="_top"><?php echo he($app_name); ?></a> di Facebook
		 <div id="share-app">
          <ul>
            <li>
              <a href="#" class="facebook-button" id="postToWall" data-url="<?php echo AppInfo::getUrl(); ?>">
                <span class="plus">Share ke dinding</span>
              </a>
            </li>
            <li>
              <a href="#" class="facebook-button apprequests" id="sendRequest" data-message="Test this awesome app">
                <span class="apprequests">Ajakin Teman</span>
              </a>
            </li>
          </ul>
        </div>
		
        </p>

      </div>
      <?php } else { ?>
      <div>
        <h1>Selamat datang</h1>
        <div class="fb-login-button" data-scope="user_likes,user_photos"></div>
      </div>
      <?php } ?>
    </header>
	
 <section id="get-started">
<stong>
Nama RJ : <?php echo ($radio_info['title']);?><br />
Quote: <strong>"<?php echo ($radio_info['description']);?>"</strong><br />
Genre: <?php echo ($radio_info['genre']);?><br />
</stong>
    </section>    
	
<div class="separator" style="clear: both; text-align: center;"><a href="http://www.poztmo.com/2012/02/rcti.html" imageanchor="1" style="margin-left: 1em; margin-right: 1em;"><img alt="RCTI TV Online Streaming" border="0" src="http://3.bp.blogspot.com/-bBzroCZlfNc/T0m1jyYV5WI/AAAAAAAABOQ/LGPz6n79fps/s1600/RCTI+TV.png" title="logo RCTI TV" /></a></div><h2 style="text-align: center;">RCTI</h2><h3 style="text-align: center;">RCTI Online Streaming Server 1</h3><div style="text-align: center;"><div style="margin: 1px;"><div class="smallfont" style="margin-bottom: 1px;"><input onclick="if (this.parentNode.parentNode.getElementsByTagName('div')[1].getElementsByTagName('div')[0].style.display != '') { this.parentNode.parentNode.getElementsByTagName('div')[1].getElementsByTagName('div')[0].style.display = ''; this.innerText = ''; this.value = 'Sembunyikan RCTI Streaming Server 1'; } else { this.parentNode.parentNode.getElementsByTagName('div')[1].getElementsByTagName('div')[0].style.display = 'none'; this.innerText = ''; this.value = 'RCTI TV Streaming Server 1'; }" style="font-size: 14px; margin: 0px; padding: 0px; width: auto;" type="button" value="RCTI TV Streaming Server 1" /></div><div class="alt2"><div style="display: none;"><div style="background: none repeat scroll 0% 0% #ffffff; padding: 10px; text-align: justify;"><center><br />
<object data="flowplayer.commercial-3.2.11.swf" height="320" id="livestream_api" name="livestream_api" type="application/x-shockwave-flash" width="540"><param name="allowfullscreen" value="true"><param name="allowscriptaccess" value="always"><param name="quality" value="high"><param name="bgcolor" value="#000000"><param name="wmode" value="transparent"><param name="flashvars" value="config={&quot;key&quot;:&quot;#$a3fff13312b0a5e029c&quot;,&quot;logo&quot;:{&quot;url&quot;:&quot;&quot;,&quot;fullscreenOnly&quot;:false,&quot;displayTime&quot;:0,&quot;bottom&quot;:25,&quot;left&quot;:5},&quot;clip&quot;:{&quot;url&quot;:&quot;http://p.okezone.com/1/bitrates.smil.xml&quot;,&quot;autoPlay&quot;:false,&quot;provider&quot;:&quot;rtmp&quot;,&quot;live&quot;:true,&quot;urlResolvers&quot;:[&quot;smil&quot;,&quot;bwcheck&quot;]},&quot;plugins&quot;:{&quot;smil&quot;:{&quot;url&quot;:&quot;flowplayer.smil-3.2.8.swf&quot;},&quot;bwcheck&quot;:{&quot;url&quot;:&quot;flowplayer.bwcheck-3.2.10.swf&quot;,&quot;serverType&quot;:&quot;wowza&quot;,&quot;dynamic&quot;:true,&quot;netConnectionUrl&quot;:&quot;rtmp://edge.okeinfo.net/live/&quot;},&quot;controls&quot;:{&quot;url&quot;:&quot;flowplayer.controls-3.2.11.swf&quot;,&quot;timeColor&quot;:&quot;#F2F2F2&quot;,&quot;timeFontSize&quot;:10,&quot;progressColor&quot;:&quot;#b01303&quot;,&quot;sliderColor&quot;:&quot;#7d7d7d&quot;,&quot;sliderGradient&quot;:&quot;none&quot;,&quot;buttonColor&quot;:&quot;#d1d1d1&quot;,&quot;buttonOverColor&quot;:&quot;#333333&quot;,&quot;disabledWidgetColor&quot;:&quot;#555555&quot;,&quot;timeBgColor&quot;:&quot;#303030&quot;,&quot;backgroundColor&quot;:&quot;#1C1C1C&quot;,&quot;buttonOffColor&quot;:&quot;rgba(90,90,90,1)&quot;,&quot;callType&quot;:&quot;default&quot;,&quot;volumeSliderColor&quot;:&quot;#595959&quot;,&quot;progressGradient&quot;:&quot;none&quot;,&quot;tooltipColor&quot;:&quot;#C9C9C9&quot;,&quot;tooltipTextColor&quot;:&quot;#D00000&quot;,&quot;backgroundGradient&quot;:&quot;none&quot;,&quot;volumeSliderGradient&quot;:&quot;none&quot;,&quot;autoHide&quot;:&quot;never&quot;,&quot;bufferGradient&quot;:&quot;none&quot;,&quot;timeBorder&quot;:&quot;1px solid rgba(0, 0, 0, 0.3)&quot;,&quot;borderRadius&quot;:&quot;0&quot;,&quot;bufferColor&quot;:&quot;#bd941f&quot;,&quot;volumeColor&quot;:&quot;#2e2e2e&quot;,&quot;height&quot;:24,&quot;opacity&quot;:1},&quot;rtmp&quot;:{&quot;url&quot;:&quot;flowplayer.rtmp-3.2.10.swf&quot;,&quot;netConnectionUrl&quot;:&quot;rtmp://edge.okeinfo.net/live/&quot;},&quot;content&quot;:{&quot;url&quot;:&quot;flowplayer.content-3.2.8.swf&quot;,&quot;top&quot;:0,&quot;left&quot;:0,&quot;width&quot;:400,&quot;height&quot;:150,&quot;backgroundColor&quot;:&quot;transparent&quot;,&quot;backgroundGradient&quot;:&quot;none&quot;,&quot;border&quot;:0,&quot;textDecoration&quot;:&quot;outline&quot;,&quot;style&quot;:{&quot;body&quot;:{&quot;fontSize&quot;:14,&quot;fontFamily&quot;:&quot;Arial&quot;,&quot;textAlign&quot;:&quot;center&quot;,&quot;color&quot;:&quot;#ffffff&quot;}}},&quot;canvas&quot;:{&quot;backgroundColor&quot;:&quot;#000000&quot;},&quot;version&quot;:[9,115],&quot;onFail&quot;:&quot;function()&quot;},&quot;playerId&quot;:&quot;livestream&quot;,&quot;playlist&quot;:[{&quot;url&quot;:&quot;http://p.okezone.com/1/bitrates.smil.xml&quot;,&quot;autoPlay&quot;:false,&quot;provider&quot;:&quot;rtmp&quot;,&quot;live&quot;:true,&quot;urlResolvers&quot;:[&quot;smil&quot;,&quot;bwcheck&quot;]}]}"></object></center></div></div></div></div></div><h3 style="text-align: center;">RCTI Online Streaming Server 2</h3><div style="text-align: center;"><div style="margin: 1px;"><div class="smallfont" style="margin-bottom: 1px;"><input onclick="if (this.parentNode.parentNode.getElementsByTagName('div')[1].getElementsByTagName('div')[0].style.display != '') { this.parentNode.parentNode.getElementsByTagName('div')[1].getElementsByTagName('div')[0].style.display = ''; this.innerText = ''; this.value = 'Sembunyikan RCTI Streaming Server 2'; } else { this.parentNode.parentNode.getElementsByTagName('div')[1].getElementsByTagName('div')[0].style.display = 'none'; this.innerText = ''; this.value = 'RCTI TV Streaming Server 2'; }" style="font-size: 14px; margin: 0px; padding: 0px; width: auto;" type="button" value="RCTI TV Streaming Server 2" /></div><div class="alt2"><div style="display: none;"><div style="background: none repeat scroll 0% 0% #ffffff; padding: 10px; text-align: justify;"><center><embed allowfullscreen="true" allowscriptaccess="always" flashvars="src=rtmp://edge.okeinfo.net/live/mncoke1_128.stream&amp;streamType=live&amp;autoPlay=true&amp;scaleMode=stretch" height="400" rln="" src="FlashMediaPlayback_101.swf" style="border: 0px solid rgb(255, 255, 255); margin-bottom: -px; margin-left: px; margin-right: 0px; margin-top: px; padding-bottom: 0px; padding-left: 0px; padding-right: 0px; padding-top: 0px;" type="application/x-shockwave-flash" width="570"></embed> Reload Browser Jika RCTI Tidak Tampil </center></div></div></div></div></div><h3 style="text-align: center;">RCTI Online Streaming Server 3</h3><div style="text-align: center;"><div style="margin: 1px;"><div class="smallfont" style="margin-bottom: 1px;"><input onclick="if (this.parentNode.parentNode.getElementsByTagName('div')[1].getElementsByTagName('div')[0].style.display != '') { this.parentNode.parentNode.getElementsByTagName('div')[1].getElementsByTagName('div')[0].style.display = ''; this.innerText = ''; this.value = 'Sembunyikan RCTI Streaming Server 3'; } else { this.parentNode.parentNode.getElementsByTagName('div')[1].getElementsByTagName('div')[0].style.display = 'none'; this.innerText = ''; this.value = 'RCTI TV Streaming Server 3'; }" style="font-size: 14px; margin: 0px; padding: 0px; width: auto;" type="button" value="RCTI TV Streaming Server 3" /></div><div class="alt2"><div style="display: none;"><div style="background: none repeat scroll 0% 0% #ffffff; padding: 10px; text-align: justify;"><center><div style="-khtml-border-radius: 4px; -moz-border-radius: 4px; -webkit-border-radius: 1px; border-radius: 4px; border: 4px solid #ccc; height: 380px; overflow: hidden; width: 510px;"><div><iframe frameborder="0" height="650" name="" scrolling="no" src="http://bagan.tv/index.php?c=130" style="margin-left: -35px; margin-top: -115px;" width="580">
</div></div></center></iframe></center></div></div></div></div></div>
		 	
    <section id="samples" class="clearfix">
                 <h3>Chat Bareng Teman lainnya dibawah ini..</h3>
<iframe src="http://www.radiocyberfm.org/chat/index.php" width="710" height="450" scrolling="no" allowtransparency="true">
								</iframe>
								
	<?php
      if ($user_id) {
    ?>

      <div class="list">
	    
        <h3>Teman yang ikutan join</h3>
        <ul class="friends">
          <?php
            foreach ($app_using_friends as $auf) {
              // Extract the pieces of info we need from the requests above
              $id = idx($auf, 'uid');
              $name = idx($auf, 'name');
          ?>
          <li>
            <a href="https://www.facebook.com/<?php echo he($id); ?>" target="_top">
              <img src="https://graph.facebook.com/<?php echo he($id) ?>/picture?type=square" alt="<?php echo he($name); ?>">
              <?php echo he($name); ?>
            </a>
          </li>
          <?php
            }
          ?>
        </ul>
      </div>
    </section>

    <?php
      }
    ?>

    <section id="guides" class="clearfix">
      <h1>The Best Indonesian Station Streaming With High Quality Sound</h1> 
	</section>
	<object width="720px" height="18" type="application/x-shockwave-flash" id="playerID" name="playerID" data="player.swf"><param name="allowfullscreen" value="false"><param name="allowscriptaccess" value="always"><param name="bgcolor" value="#FFFFFF"><param name="flashvars" value="type=sound&autostart=true&file=http://radio.for-our.info:8000/stream" allowfullscreen="false" quality="high"></object>
  </body>
</html>
