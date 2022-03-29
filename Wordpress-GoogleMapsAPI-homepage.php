<?php

// google-home.php
// Last Revision: 06/06/2019
//
// Purpose: This a page tmeplate for the main page of homepage. This page calls the Google Maps Api and whilst displaying all
// the other page elements via Wordpress. 
//
// This code also connects to a MySQL database that houses all the users uploads and their
// location on the webiste. Check $con arguments and make sure the correct .sql file is on the MySQL server.
//
// The AJAX call for the 'local news sidebar' calls a specific file called 'finalparse.php' that is the source for pulling the local news
// based upon the events (locations) of the Google API Map. Make sure the file directory is relavant to the AJAX call.  


get_header();

$videopro_sidebar = get_post_meta(get_the_ID(),'page_sidebar',true);
if(!$videopro_sidebar){
	$videopro_sidebar = ot_get_option('page_sidebar','both');
}
if($videopro_sidebar == 'hidden') $videopro_sidebar = 'full';
$videopro_page_title = videopro_global_page_title();
$videopro_layout = videopro_global_layout();
$videopro_sidebar_style = 'ct-small';
videopro_global_sidebar_style($videopro_sidebar_style);

// Create connection
$con = new mysqli("localhost", "root", "", "wordpress");

if (mysqli_connect_errno())
  {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
  }

?>

<head>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.0/jquery.min.js"></script>
<meta name="viewport" content="initial-scale=1.0, user-scalable=no">
    <meta charset="utf-8">
    <title>Places Search Box</title>
    <style>
      /* Always set the map height explicitly to define the size of the div
       * element that contains the map. */
      #map {
        height: 600px;
      }
     
      #description {
        font-family: Roboto;
        font-size: 15px;
        font-weight: 300;
      }

      #infowindow-content .title {
        font-weight: bold;
      }

      #infowindow-content {
        display: none;
      }

      #map #infowindow-content {
        display: inline;
      }

      .pac-card {
        margin: 10px 10px 0 0;
        border-radius: 2px 0 0 2px;
        box-sizing: border-box;
        -moz-box-sizing: border-box;
        outline: none;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
        background-color: #fff;
        font-family: Roboto;
      }

      #pac-container {
        padding-bottom: 12px;
        margin-right: 12px;
      }

      .pac-controls {
        display: inline-block;
        padding: 5px 11px;
      }

      .pac-controls label {
        font-family: Roboto;
        font-size: 13px;
        font-weight: 300;
      }

      #pac-input {
        background-color: #fff;
        font-family: Roboto;
        font-size: 15px;
        font-weight: 300;
        margin-left: 12px;
        padding: 0 11px 0 13px;
        text-overflow: ellipsis;
        width: 400px;
      }

      #pac-input:focus {
        border-color: #4d90fe;
      }

      #title {
        color: #fff;
        background-color: #4d90fe;
        font-size: 25px;
        font-weight: 500;
        padding: 6px 12px;
      }
      #target {
        width: 345px;
      }
    </style>
  </head>
    <!--body content-->
    <!-- The body content here is all wordpress jargon and mainly from the template of another wordpress file in the templates folder.-->
    <div id="cactus-body-container">
    
        <div class="cactus-sidebar-control <?php if($videopro_sidebar=='right' || $videopro_sidebar=='both'){?>sb-ct-medium <?php }?>  <?php if($videopro_sidebar!='full' && $videopro_sidebar!='right'){?>sb-ct-small <?php }?>"> <!--sb-ct-medium, sb-ct-small-->
        
            <div class="cactus-container <?php if($videopro_layout=='wide'){ echo 'ct-default';}?>">                        	
                <div class="cactus-row">
                    <?php if($videopro_layout == 'boxed' && ($videopro_sidebar == 'both')){?>
                        <div class="open-sidebar-small open-box-menu"><i class="fas fa-bars"></i></div>
                    <?php }?>
                    <?php if($videopro_sidebar == 'left' || $videopro_sidebar == 'both'){ get_sidebar('left'); } ?>
                    
                    <div class="main-content-col">
                        <div class="main-content-col-body">
                        	<div class="single-page-content">
                                <article class="cactus-single-content">                                	
									<?php 	
									if(!is_page_template('page-templates/front-page.php')){								
										videopro_breadcrumbs();
										?>                        
										<h1 class="single-title entry-title"><?php echo esc_html($videopro_page_title);?></h1>
										<?php 
									}else{
										echo '<h2 class="hidden-title">'.esc_html($videopro_page_title).'</h2>';
									}?>
                                    <?php
									if(is_active_sidebar('content-top-sidebar')){
                                        echo '<div class="content-top-sidebar-wrap">';
                                        dynamic_sidebar( 'content-top-sidebar' );
                                        echo '</div>';
                                    } ?>
                                  
    <input id="pac-input" class="controls" type="text" placeholder="Search Box">
    <div id="map"></div>                                
                                    
    <script>
       
        
    /* This function updates the rss feed seen on the left hand side of the website. The call is made to the location of finalparse.php 
        and updates the feed with correct feed information based upon the location selected in the google api map */
        
        
    function update_RSS_feed(location_value){
        url = 'location/finalparse.php?location=' + location_value;
        console.log(url);
        $.ajax({
            type: 'GET',
            url: 'http://localhost/finalparse.php?location=' + location_value,
            datatype: 'text/html',
            success: function(data){
                console.log('updating RSS Feed IN ajax call');
                console.log(data);
                document.getElementById('RSS_feed_contents').innerHTML = data;
        }});
    }
        
        /* This is where the real API code is called and begins. Note that this code has the following capabilites
                1) Geolocation call is made to detect the users location (if they so willingly share it with you)
                    and place the map at the center of the said location. 
                
                2) The map features an 'Auto-Complete' search bar that predicts the users input based upon location.
                
                
                3) The map houses all the markers with user uploads. A marker is made based upon a MySQL call made to the database
                    to grab the location of the uploaded user videos.
                    
                
                4) The map is stylized by the head */
                    
        
var geocoder;
var map;
var infoWindow;
function initMap() {
    infoWindow = new google.maps.InfoWindow;
    geocoder = new google.maps.Geocoder();

    var uluru = { lat: -25.363, lng: 131.044 };
    map = new google.maps.Map(document.getElementById('map'), {
        zoom: 11,
        center: uluru
    });
    var marker = new google.maps.Marker({
        position: uluru,
        map: map
    });
    if (navigator.geolocation) {
          navigator.geolocation.getCurrentPosition(function(position) {
            var pos = {
              lat: position.coords.latitude,
              lng: position.coords.longitude
            };

            map.setCenter(pos);

          }, function() {
            handleLocationError(true, infoWindow, map.getCenter());
          });
        } else {
          // Browser doesn't support Geolocation
          handleLocationError(false, infoWindow, map.getCenter());
        }


        function handleLocationError(browserHasGeolocation, infoWindow, pos) {
          infoWindow.setPosition(pos);
          infoWindow.setContent(browserHasGeolocation ?
            'Error: The Geolocation service failed.' :
            'Error: Your browser doesn\'t support geolocation.');
          infoWindow.open(map);
        }
    
    
         // Create the search box and link it to the UI element.
        var input = document.getElementById('pac-input');
        var searchBox = new google.maps.places.SearchBox(input);
        map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);

        // Bias the SearchBox results towards current map's viewport.
        map.addListener('bounds_changed', function() {
          searchBox.setBounds(map.getBounds());
        });

        // Listen for the event fired when the user selects a prediction and retrieve
        // more details for that place.
        update_rss_feed = false;
        searchBox.addListener('places_changed', function() {
              
          var places = searchBox.getPlaces();
            console.log(places[0]['formatted_address']);

          if (places.length == 0) {
            return;
          }
          else {
              update_rss_feed = true;
          }

          // Clear out the old markers.
          //markers.forEach(function(marker) {
            //marker.setMap(null);
          //});
          markers = [];

          // For each place, get the icon, name and location.
          var bounds = new google.maps.LatLngBounds();
          places.forEach(function(place) {
            if (!place.geometry) {
              console.log("Returned place contains no geometry");
              return;
            }
            var icon = {
              url: place.icon,
              size: new google.maps.Size(71, 71),
              origin: new google.maps.Point(0, 0),
              anchor: new google.maps.Point(17, 34),
              scaledSize: new google.maps.Size(25, 25)
            };

            // Create a marker for each place.
            markers.push(new google.maps.Marker({
              map: map,
              icon: icon,
              title: place.name,
              position: place.geometry.location
            }));

            if (place.geometry.viewport) {
              // Only geocodes have viewport.
              bounds.union(place.geometry.viewport);
            } else {
              bounds.extend(place.geometry.location);
            }
          });
          map.fitBounds(bounds);
          if(update_rss_feed == true){
              console.log('Updating RSS feed with ajax call');
              update_RSS_feed(places[0]['formatted_address'].replace(/\s+/g, ''));
          }
        });
    

              

    /* These are database calls that place the selected query in an array. This happens both for the locations and videos. 
       The index of each array should correlate to another */
    
    <?php
         $arr = [];
$query = mysqli_query($con, "SELECT * FROM `wp_cf7_vdata_entry` WHERE name = 'location-636'");
if (!$query) {
    printf("Error: %s\n", mysqli_error($con));
    exit();
}
while($row = mysqli_fetch_array($query, MYSQLI_ASSOC))
    {

    $bs = $row['value'];
    $arr[] = $bs;
    }


        $videoarray = [];
        $query2 = mysqli_query($con, "SELECT * FROM `wp_cf7_vdata_entry` WHERE name = 'video-file'");
        if (!$query2) {
        printf("Error: %s\n", mysqli_error($con));
        exit();
        }
    while($videorow = mysqli_fetch_array($query2, MYSQLI_ASSOC))
    {

    $bs2 = $videorow['value'];
    $videoarray[] = $bs2;
    
    }
    
    for ($i = 0; $i < sizeof($arr); $i++) {
        $multiple = 'false';
        for($j = 0; $j < sizeof($arr); $j++){
            if($i == $j)
            {
                continue;
            }
            if($arr[$i] == $arr[$j])
            {
                $multiple = 'true';
                break;
            }
        }
    echo ("codeAddress('$arr[$i]', '$videoarray[$i]', '$multiple');"); }
        
        ?>
}
        
        // codeAddress actually does the magic that creates the markers and the content windows for all the user uploads found in the database. 

function codeAddress(address, video, multiple) {
    
    var markers = [];
    
        var str = "<div id=\"content\">" +
              "<div id=\"siteNotice\">" +
              "</div>" +
              "<h1 id=\"firstHeading\" class=\"firstHeading\">Video in Info Window</h1>" +
              "<div id=\"bodyContent\">" +
              "<iframe width=\"640\" height=\"390\" src=\"" +
            video +
            "\" frameborder=\"0\" allowfullscreen></iframe>" +
              "</div>" +
              "</div>";
    
     // Case: For when there are multiple videos uploaded to the location. There will be a link that guides the user to all the uploaded videos.
      var multiple_str = "<div id=\"multiple\">" +
                            '<p style=\"color:red;\">Multiple videos from here! <a style=\"color:blue;\" href="http://localhost/wordpress/all-videos/">'+
            'http://localhost/wordpress/all-videos/</a> '+
            '</p>'+
            "</div>";
    
    if(multiple == 'true')
        {
            str += multiple_str;
        }
        
    // Take the longitude and latidute postion of the address and attach a marker to it. Listen for a click on the marker to open the infowindow that
    // houses the video
    geocoder.geocode({ 'address': address }, function (results, status) {
        console.log(results);
        var latLng = {lat: results[0].geometry.location.lat (), lng: results[0].geometry.location.lng ()};
        console.log (latLng);
        if (status == 'OK') {        
            var Icon = { 
              path: google.maps.SymbolPath.CIRCLE, 
              scale: 5
            };

            // Create a marker for each place.
            markers.push(new google.maps.Marker({
              map: map,
              icon: Icon,
              position: latLng
            }));

                        for (id in markers) {
                            console.log ('ID: ' + id);
              markers[id].addListener('click', function(event) {
                var infowindow = new google.maps.InfoWindow({
                  content: str
                });
                infowindow.setPosition(event.latLng);
                infowindow.open(map);
              })
            };
            console.log (map);
            console.log (str);
            }
         else {
            alert('Geocode was not successful for the following reason: ' + status);
        }
    });
  }
    </script>
    <script src="https://maps.googleapis.com/maps/api/js?key=**************************&libraries=places&callback=initMap"
         async defer></script>
                                    <?php
									
									if(is_active_sidebar('content-bottom-sidebar')){
                                        echo '<div class="content-bottom-sidebar-wrap">';
                                        dynamic_sidebar( 'content-bottom-sidebar' );
                                        echo '</div>';
                                    } ?>
                                </article>
                            </div>
                        </div>
                    </div>
                    
                    <?php 
					$videopro_sidebar_style = 'ct-medium';
					videopro_global_sidebar_style($videopro_sidebar_style);
					if($videopro_sidebar=='right' || $videopro_sidebar=='both'){ get_sidebar(); } ?>
                    
                </div>
            </div>
            
        </div>                
        
        
    </div><!--body content-->

<?php get_footer();