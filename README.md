# 2140 meetups integrations

<p align="center"><img src="./docs/assets/cover.png" alt="integration cover"></p>

A container that creates different JSON files for specific purpose:

1. Create a global OSM map to display all the communities of [2140 meetups](https://2140meetups.com/). The creation of each point will be based on the communities that stores the 2140 meetups data base
2. Create individual files for each community that after [BTC Maps](https://btcmap.org/) will render in its own map. That data will be available through an API endpoint

## Spin up the container

To create all the JSON files, follow the bellow steps:

1. Build a container

```bash
docker build -t 2140-sandbox .
```

2.Run container

```bash
# add remove option to remove the container when exists
docker run --rm -v ./btcmaps:/usr/src/2140_geo/btcmaps -v ./leaflet:/usr/src/2140_geo/leaflet --name meetups-integration 2140-sandbox
```

3.Kill container

```bash
# In our case the name is meetups-integration
docker kill container_name
```

## Docker images

The container is not implemented in a clean way and each time that we build, it creates a new image. To delete the duplicated images with `<none>` tag, execute that to delete:

```bash
docker images -a | grep none | awk '{ print $3; }' | xargs docker rmi  
```

## URL

- [API endpoint for BTCMaps](https://2140meetups.com/wp-json/btcmap/v1/communities)
- The leaflet file that we will use to render the map: [geo.json](https://gist.githubusercontent.com/bozdoz/064a7101b95a324e8852fe9381ab9a18/raw/ee100561f5a0a8cf55430e9f2157e4a1e2560a2e/map.geojson) file
- [Nominatim](https://nominatim.openstreetmap.org)
- [Nominatim API](https://nominatim.org/release-docs/develop/api/Search/)
- [GeoJson area](https://geojson.io)
- [Country Codes](https://countrycode.dev/)
- [Polygons OSM](https://polygons.openstreetmap.fr)
- [API Ninjas](https://api-ninjas.com/register) - Deprecated
