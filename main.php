<?php

	// Operation types to interact with the JSON file
	const UPDATE = 'update';
	const DELETE = 'delete';

	const PIN_COLOR = "#FA402B";
	const PIN_COLOR = "#FA402B";
	const PIN_SIZE = "medium";

	//############## PIN OBJECT CREATION
	//-------------------------------------
	
	function create_pin_style($_id, $name)
	{
		return array(
			"id" => $_id,
			"marker-color" => PIN_COLOR,
			"fill" => PIN_COLOR,
			"stroke" => PIN_COLOR,
			"marker-size" => PIN_SIZE,
			"popup-text" => $name,
		);
	}
	
	function create_pin_coordinates($latitude, $longitude)
	{
		return array(
			"type" => "Point",
			"coordinates" => array($longitude, $latitude),
		);
	}

	//############## MAP RELATED OPERATIONS
	//-------------------------------------

	/**
	 * The pin (meetup) does not exist in the geo.json file. Add new point to visualise
	 */
	function add_new_point_in_map($_id, $name, $latitude, $longitude)
	{
		$new_point = array(
			"type" => "Feature",
			"properties" => create_pin_style($_id, $name),
			"geometry" => create_pin_coordinates($latitude, $longitude),
		);

		$map = get_map_content();
		array_push($map["features"], $new_point);
		$json_map = json_encode($map);
		update_leaflet_map($json_map);
	}

	/**
	 * The pin already exist in the map so, we need to UPDATE the pin (meetups) properties
	 */
	function update_map_point($_id, $name, $latitude, $longitude)
	{
		$pin_to_edit = find_pin($_id);
		$pin_content = $pin_to_edit["content"];
		$pin_index = $pin_to_edit["index"];
		// Edit pin values
		$pin_content["properties"]["popup-text"] = $name;
		// Assuming 0 index is latitude
		$pin_content["geometry"]["coordinates"][0] = $longitude;
		// Assuming 1 index is longitude
		$pin_content["geometry"]["coordinates"][1] = $latitude;
		edit_and_save_map($pin_content, $pin_index);
	}

	/**
	 * The meetup already does not exist so, delete the pin from the geo.json file
	 */
	function remove_map_point($_id)
	{
		$map = delete_pin_index($_id);
		$json_map = json_encode($map);
		update_leaflet_map($json_map);
	}

	//!!!! ############## MAIN FUNCTION TO INTERACT WITH THE APPLICATION GEO.JSON FILE
	//---------------------------------------------------------------------------

	/**
	 * Update the new JSON file depending of action type
	 * @param int $id Unique identificator of the meetup
	 * @param string $action UPDATE | DELETE
	 * @param string $latitude
	 * @param string $longitude
	 * @return null
	 */
	function generate_new_geo_json_map($_id, $action, $name = null, $latitude = null, $longitude = null)
	{
		if ($action == UPDATE)
		{
			$meetup_pin = find_pin($_id);

			if ($meetup_pin == null)
			{
				add_new_point_in_map($_id, $name, $latitude, $longitude);
			} 
			else
			{
				update_map_point($_id, $name, $latitude, $longitude);
			}
		} 
		else
		{
			remove_map_point($_id);
		}
	}

	//!!!! ############## MAIN FUNCTION TO EXTRACT COMMUNITY AREA FROM NOMINATIM
	//---------------------------------------------------------------------------

	/**
	 * Create or update the btc map area JSON
	 * @param int $_id Unique identificator of the meetup
	 * @param string $country
	 * @param string $city
	 * @return null
	 */
	function generate_area_from_btcmaps($row) {
		// Set the file name
		$file_name = "btc_map_area_" . $row["id"] . ".json";
		// Extract the info from different APIs
		$remote_data = request_remote_data($row["osm_id"]);
		// Extract from local data the missing attributes
		$local_data = extract_local_data($row);
		$btc_maps_community = merge_remote_and_local_data($remote_data, $local_data);

		// Decode the JSON file
		$btc_maps_json = json_encode($btc_maps_community, JSON_UNESCAPED_SLASHES);
		// Create or update the file
		update_btc_map_area_file($file_name, $btc_maps_json);
		
		return $btc_maps_community;
	}

	//!!!! ############## BTC MAPS ENDPOINT TO FETCH ALL THE COMMUNITIES
	//---------------------------------------------------------------------------
	function btc_maps_endpoint()
	{
		// Ignore the first two indexes because are the actual folder and the parent one
		$btc_maps_library = array_slice(scandir(BTCMAPS_FOLDER), 2);
		$communities = array();
		foreach ($btc_maps_library as $file_name) 
		{
			$community = get_community_file($file_name);
			array_push($communities, $community);
		}
		$integration_communities = json_encode($communities, JSON_UNESCAPED_SLASHES);
		// No need for integration. Just for testing
		update_btc_map_area_file("integration_data.json", $integration_communities);
		return $integration_communities;
	}
