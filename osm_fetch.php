<?php

function fetch_osm_from_meetups_id()
{
    $communities = fetch_all_the_communities();

    $osm_ids = array();

    foreach ($communities as $key => $community) 
    {
        $new_community = array(
            "id"		=> $community->id,
            "city"	    => $community->ciudad,
            "country"   => $community->pais
        );

        $location = encode_community_location($new_community["city"], $location["country"]);

        $URL_NOMINATIM = sprintf(NOMINATIM_OPENSTREETMAP, $new_community["city"], $location["country"]);
        $community_metadata = get_community_metadata($URL_NOMINATIM, $new_community["city"]);

        $osm = array("osm_id" => $community_metadata["tags"]["osm_id"], "meetups_id" => $new_community["id"]);

        array_push($osm_ids, $osm);
    }

    foreach ($osm_ids as $key => $community)
    {
        $osm_id = $community["osm_id"];
        $meetups_id = $community["meetups_id"];

        print_r("OSM: " . $osm_id . ", MeetupID: " . $meetups_id . "\n");
    }
}