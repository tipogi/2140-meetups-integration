<?
	include './main.php';
	include './helper.php';
	include './osm_fetch.php';

	const CREATE_LEAFLET_JSON = false;
	const CREATE_BTCMAP_JSON = true;
	const SHOW_OSM_ID_COMMUNITY = false;

	const COMMUNITIES_ENDPOINT = "http://2140meetups.com/wp-json/wp/v2/community?per_page=100";


	function main()
	{
		$communities = fetch_all_the_communities();

		if (CREATE_LEAFLET_JSON)
		{
			foreach ($communities as $key => $community) 
			{
				print_r($community->title->rendered . "\n");
				generate_new_geo_json_map(
					$community->id, 
					UPDATE /*DELETE*/,
					$community->title->rendered, 
					$community->lat, 
					$community->lon
				);
				// #NEW: There is a rate limit to query OSM
				sleep(2);
			}

			//render_leaflet_map();

			print_r("\nEND creating LEAFLET JSON");
		}

		if (CREATE_BTCMAP_JSON)
		{

			$new_communities = array();

			print_r("\nTotal communities in 2140meetups: " . count($communities) . "\n");
			// Add the header for BTC Maps JSON files
			preview_remote_results_header();

			foreach ($communities as $key => $community) 
			{
				$new_community = array(
					"id"		=> $community->id,
					"osm_id"	=> $community->osm_id,
					"email"		=> "privacy@policy.org",
					"telegram"	=> $community->telegram,
					"imagen"	=> "https://2140meetups.com/wp-content/uploads/2023/02/639b9f9c3841d3112e52d79a_website-banner-image3-p-2000.png",
					"nombre"	=> $community->slug,
					"ciudad"	=> $community->ciudad,
					"pais"		=> $community->pais,
				);

				generate_area_from_btcmaps($new_community);
				// #NEW: There is a rate limit to get country continent: 10 per 1 minute
				sleep(5);
			}

			print_r("\nEND creating BTCMAP JSON");
		}

		if (SHOW_OSM_ID_COMMUNITY)
		{
			fetch_osm_from_meetups_id($communities);
		}
	}
	
	/**
	 * Fetch all the communities of 2140 meetups
	 */
	function fetch_all_the_communities()
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
		// Activate, if not it prints in the console
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt($ch, CURLOPT_URL, COMMUNITIES_ENDPOINT);
		// Execute the request
		$response = curl_exec($ch);
		// Clear up CURL
		curl_close($ch);

		return json_decode($response);
	}

	// Run the script
	main();