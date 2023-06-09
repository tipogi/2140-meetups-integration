<?php

# Files and folders
define("LEAFLET_FILE", __DIR__ . '/leaflet/geo.json');
define("BTCMAP_FOLDER", __DIR__ . '/btcmaps/');
# BTC MAP integration constants
const NOMINATIM_OPENSTREETMAP = "https://nominatim.openstreetmap.org/details.php?osmtype=R&osmid=%s&format=json&addressdetails=1&extratags=1&email=hello@2140meetups.com";
const CONTINENT_API = "https://restcountries.com/v3.1/name/%s";
const POLYGONS_OPENSTREETMAP = "https://polygons.openstreetmap.fr/get_geojson.py?id=%s&params=0.020000-0.005000-0.005000";
const POLYGONS_OPENSTREETMAP_MAP_GENERATION = "https://polygons.openstreetmap.fr/?id=%s";

// Deprecated
const CITY_NINJA = "https://api.api-ninjas.com/v1/city?name=%s";
const NINJA_API_KEY = "X-Api-Key: xxxxxxxxxxxx";
const NOMINATIM_OPENSTREETMAP_SEARCH = "https://nominatim.openstreetmap.org/search?format=json&polygon_geojson=1&polygon_threshold=0.0003&city=%s&country=%s&email=hello@2140meetups.com&addressdetails=1&extratags=1";
const COUNTRY_CODE = "https://countrycode.dev/api/countries/iso2/%s";

// ---------------------------------------------------------------------------
// ################## GENERATE DATA FOR GEO.JSON FILE ################## 
// ---------------------------------------------------------------------------

/**
 * Reads the geo.json file content and return a populated array with map points
 */
function get_map_content()
{
	// Read the JSON file 
	$geo_json = file_get_contents(LEAFLET_FILE);
	// Error control
	if ($geo_json === FALSE)
	{
		$map = array(
			"features" => array()
		);
	}
	else
	{
		// Decode the JSON file
		$map = json_decode($geo_json, true);
	}
	// Display data
	return $map;
}

function render_leaflet_map()
{
	$geo_json = file_get_contents(LEAFLET_FILE);
	$map = json_decode($geo_json, true);
	print_r($map);
}

/**
 * Once we have ready map json, overwrite the actual geo.json
 */
function update_leaflet_map($json_map)
{
	file_put_contents(LEAFLET_FILE, $json_map);
}

/**
 * Construct the map object
 */
function edit_and_save_map($pin_content, $pin_index)
{
	$map = get_map_content();
	$map["features"][$pin_index] = $pin_content;
	update_leaflet_map(json_encode($map));
}

// ____ SEARCH OPERATIONS IN GEO.JSON FILE ____

/**
 * Find the pin object in the map (geo.json)
 */
function find_pin($id_to_find)
{
	$map = get_map_content();

	foreach ($map["features"] as $key => $pin) 
	{
		$id = $pin["properties"]["id"];
		if ($id === $id_to_find) 
		{
			return array(
				"content" => $pin,
				"index" => $key
			);
		}
	}
	return null;
}

/**
 * Find the pin in the map (geo.json) list and if it exists, delete from the map
 */
function delete_pin_index($id_to_delete)
{
	$map = get_map_content();
	$index_to_delete = null;

	foreach ($map["features"] as $key => $pin) 
	{
		$id = $pin["properties"]["id"];
		if ($id === $id_to_delete) 
		{
			$index_to_delete = $key;
			break;
		}
	}
	// If we find the index, delete the pin object
	if ($index_to_delete != null)
	{
		unset($map["features"][$index_to_delete]);
	}

	$map["features"] = array_values($map["features"]);
	return $map;
}

// -------------------------------------------------------------
// ################## BTC MAPS INTEGRATION ##################
// -------------------------------------------------------------

/**
 * All the important data in btc maps area has to go in tags property
 * @param $remote_data: Nominatim area and api ninja population
 * @param $local_data: Complete the tags field for the integration
 */
function merge_remote_and_local_data($remote_data, $local_data)
{
	$local_data["tags"] = array_merge($remote_data, $local_data["tags"]);
	return $local_data;
}

/**
 * Integration Complementary data
 * @param: The local row of the community
 */
function extract_local_data($community)
{
	return array(
		"id"	=> "2140_meetups_" . $community["id"],
		"tags"	=> array(
			"contact:email" 	=> $community["email"],
			"contact:telegram" 	=> $community["telegram"],
			"icon:square"		=> $community["imagen"],
			"name"				=> $community["nombre"],
			"type"				=> "community",
			"organization"		=> "2140-meetups"
		)
	);
}

/**
 * Add remote content of the community to serve to btcmap request
 * @param: osm_id
 * return array
 */
function request_remote_data($osm_id)
{
	$URL_NOMINATIM = sprintf(NOMINATIM_OPENSTREETMAP, $osm_id);
	$community_metadata = get_community_metadata($URL_NOMINATIM, $osm_id);

	$geo_json_polygon = get_city_area($osm_id);

	$remote_data = array_merge($community_metadata, $geo_json_polygon);

	preview_remote_results($remote_data);

	return $remote_data;
}

/**
 * Get the city population and continent
 * @param $url: The request url
 */
function get_community_metadata($url, $osm_id) 
{
	// Execute the request
	$location_metadata = make_get_request($url);
	
	$nominatim_key = 0;

	// Error control if we get the right nominatim response
	if (
		!empty($location_metadata) && 
		property_exists($location_metadata, "country_code") &&
		property_exists($location_metadata, "extratags"))
	{
		// Might not have the population date
		$population_date = property_exists($location_metadata->extratags, "population:date") ? 
			$location_metadata->extratags->{'population:date'} 
			: 'na';

		$nominatim_object = array(
			"population"		=> $location_metadata->extratags->population,
			"population:date"	=> $population_date,
		);

		// Before get the continent, find the country
		$country = get_city_country($location_metadata);

		if ($country != null)
		{
			$url = sprintf(CONTINENT_API, $country);
			
			// Execute the request
			$parsed_nominatim_result = make_get_request($url);
	
			if (
				!empty($parsed_nominatim_result) && 
				array_key_exists(0, $parsed_nominatim_result) &&
				property_exists($parsed_nominatim_result[0], "continents") &&
				array_key_exists(0, $parsed_nominatim_result[0]->continents)) 
			{
				$continent = $parsed_nominatim_result[0]->continents[0];
				$nominatim_object["continent"] = $continent;
				$continent_cache[$country] = $continent;
			}
		} else
		{
			$nominatim_object["continent"] = null;
		}
		
		return $nominatim_object;
	}
	return array(
		"continent" 	=> null,
		"population"	=> null
	);
}

function get_city_country($location_metadata) 
{
	if (property_exists($location_metadata, "address")) 
	{
		foreach ($location_metadata->address as $key => $address)
		{
			if ($address->type == "country")
			{
				// Some country comes with slashes representing different way to say the country
				// Delete also confusing spaces for the API at the beginning and end of the country
				$split_slashes = trim(explode('/', $address->localname)[0]);
				$delete_white_spaces = str_replace(' ', '%20', $split_slashes);
				return delete_tilde($split_slashes);
			}
		}
	}
	return null;
}

/**
 * OpenStreetMap polygons are quite sharp, we want a bit more extense the area
 * @param $url: The requested API endpoint
 */
function get_city_area($osm_id)
{
	// Generate the area that we want with a POST request
	$POST_POLYGONS = sprintf(POLYGONS_OPENSTREETMAP_MAP_GENERATION, $osm_id);
	// Create the body for the POST request
	$body = array(
		'x' => '0.020000',
		'y' => '0.005000',
		'z' => '0.005000'
	);
	// We do not care the response because we just want that it would be avaible that area
	// AIM: Create the need it area JSON file
	make_post_request($POST_POLYGONS, $body);

	// Once the JSON of area is generated, request it
	$GET_POLYGONS = sprintf(POLYGONS_OPENSTREETMAP, $osm_id);
	$parsed_area_result = make_get_request($GET_POLYGONS);

	return array(
		"geojson" => $parsed_area_result
	);
}


// #######################################################
// ############### HELPER FUNCTIONS ######################
// #######################################################

/**
 * Some API endpoints does not understand tildes
 * @param chain: The word that we want to edit 
 */
function delete_tilde($chain) 
{
	// We encode the string in utf8 format in case we get errors
	//$chain = utf8_encode($chain);

	// Now we replace the letters
	$chain = str_replace(
		array('á', 'à', 'ä', 'â', 'ª', 'Á', 'À', 'Â', 'Ä'),
		array('a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A'),
		$chain );

	$chain = str_replace(
		array('é', 'è', 'ë', 'ê', 'É', 'È', 'Ê', 'Ë'),
		array('e', 'e', 'e', 'e', 'E', 'E', 'E', 'E'),
		$chain );

	$chain = str_replace(
		array('í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î'),
		array('i', 'i', 'i', 'i', 'I', 'I', 'I', 'I'),
		$chain );

	$chain = str_replace(
		array('ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô'),
		array('o', 'o', 'o', 'o', 'O', 'O', 'O', 'O'),
		$chain );

	$chain = str_replace(
		array('ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Û', 'Ü'),
		array('u', 'u', 'u', 'u', 'U', 'U', 'U', 'U'),
		$chain );

	/*$chain = str_replace(
		array('ñ', 'Ñ', 'ç', 'Ç'),
		array('n', 'N', 'c', 'C'),
		$chain );*/

	return $chain;
}

/**
 * A generic GET request
 * @param $url: API endpoint
 * @param $header: Extra headers of the request
 */
function make_get_request($url, $headers = array())
{
	$ch = curl_init();

	if (!empty($headers))
	{
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	}
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
	// Activate, if not it prints in the console
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt($ch, CURLOPT_URL, $url);
	// Execute the request
	$response = curl_exec($ch);
	// Clear up CURL
    curl_close($ch);
	return json_decode($response);
}

/**
 * A generic POST request
 * @param $url: API endpoint
 * @param $body: Request body
 */
function make_post_request($url, $body)
{
	$ch = curl_init();

	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_URL, $url);
	// Populate the body of the request with hard coded constants
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($body));
	// Activate, if not it prints in the console
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	// Execute the request
	$response = curl_exec($ch);
	// Clear up CURL
    curl_close($ch);

	return $response;
}

/**
 * Create or update the area JSON file
 * @param $filename
 * @param $json_area: The content of the file
 */
function update_btc_map_area_file($file_name, $json_area)
{
	file_put_contents(BTCMAP_FOLDER . $file_name, $json_area);
}

/**
 * Once we have all the community files created, get the file to serve BTCMaps
 */
function get_community_file($file_name)
{
	$community_json = file_get_contents(BTCMAP_FOLDER . $file_name);
	// Decode the JSON file
	$community = json_decode($community_json, true);
	return $community;
}



########################################
##### NOT IN USE. DEPRECATED ###########
########################################

/**
 * In nominatim not all the addresses has city. They might have instead village or town
 * @param $address: The object that has the location metadata
 */
function get_city($address)
{
	if (property_exists($address, "city"))
	{
		return $address->city;
	}
	else if (property_exists($address, "village"))
	{
		return $address->village;
	} 
	else if (property_exists($address, "town"))
	{
		return $address->town;
	}
	return "";
}

/**
 * If we have composed location format spaces and tildes in case it has
 * @param $city
 * @param $country
 */
function encode_community_location($city, $country)
{
	//print_r("DB => City: ". $city . ", Country: " . $country . "\n");
	// Delete tildes to avoid empty result
	$city_without_tilde = delete_tilde($city);
	// Adapt the spaces to avoid empty result
	$formatted_city = str_replace(' ', '%20', $city_without_tilde);
	// Delete tildes to avoid empty result
	$country_without_tilde = delete_tilde($country);
	// Adapt the spaces to avoid empty result
	$formatted_country = str_replace(' ', '%20', $country_without_tilde);
	
	return array(
		"city"		=> $formatted_city,
		"country"	=> $formatted_country
	);
}

function preview_remote_results($remote_data)
{
	$area = empty($remote_data["geojson"]) ? "NO" : "YES";
	print_r($area . "\t\t" . $remote_data["population"] . "\t\t" . $remote_data["continent"] . "\n\n");
	//print_r("=> DB: City: " . $city . ", Country: " . $country . "\n\n");
}

function preview_remote_results_header()
{
	print_r("HAS AREA\tPOPULATION\tCONTINENT\n");
}