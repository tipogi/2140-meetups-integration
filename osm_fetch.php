<?php

function fetch_osm_from_meetups_id($communities)
{
    $osm_ids = array();

    foreach ($communities as $key => $community) 
    {
        $city = $community->ciudad;
        $country = $community->pais;

        $new_community = array(
            "id"		=> $community->id,
            "city"	    => $city,
            "country"   => $country
        );

        $location = encode_community_location($city, $country);

        $URL_NOMINATIM = sprintf(NOMINATIM_OPENSTREETMAP, $location["city"], $location["country"]);
        $community_metadata = get_community_metadata($URL_NOMINATIM, $city);

        print_r($community_metadata);

        $osm = array(
            "osm_id" => $community_metadata["osm_id"], 
            "meetups_id" => $new_community["id"],
            "city" => $city,
            "country" => $country
        );

        print_r($osm);

        array_push($osm_ids, $osm);

        sleep(1);
    }

    foreach ($osm_ids as $key => $community)
    {
        $osm_id = $community["osm_id"];
        $meetups_id = $community["meetups_id"];
        $city = $community["city"];
        $country = $community["country"];

        print_r("OSM: " . $osm_id . ", MeetupID: " . $meetups_id . ", City: " . $city . ", Country: " . $country . "\n");
    }
}