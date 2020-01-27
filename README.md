# jmeter-grafana

Quick and dirty jMeter results visualization

## Installation

```bash
git clone git@github.com:jensschulze/jmeter-grafana.git
cd jmeter-grafana
composer install
```

## Run it

```bash
bin/server/start
bin/convert
```

## Query/visualize

You can reach Grafana on `127.0.0.1:3000`. You may import the dashboard JSON configuration file in the `conf` directory.
Done.

