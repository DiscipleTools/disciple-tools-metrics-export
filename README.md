[![Build Status](https://travis-ci.com/DiscipleTools/disciple-tools-metrics-export.svg?branch=master)](https://travis-ci.com/DiscipleTools/disciple-tools-metrics-export)

# Disciple Tools - Metrics Export

Export CSV, JSON, KML, and GEOJSON file types of contacts and groups. Create distributable public links as either one-time download, expiring, or permanent link access. Extendable to facilitate your own downloads with a developer starter plugin.

## Purpose

The key to this plugin is that it is expandable to serve other kinds of exports beyond the default export.

## Usage
#### Will Do

- Adds CSV exports for contacts and groups. Can export with longitude and latitude for import into mapping applications like Google Earth, Arc GIS, Google Maps, Google Data Studio.
- Adds JSON exports for contacts and groups. When combined with a permanent link, it can be used in other applications to draw maps or other visiualizations of data.
- Adds KML exports for contacts and groups. KML can be used in Google Earth and Google Earth Pro for mapping.
- Adds GEOJSON exports for contacts and groups. GEOJSON is a open standard for mapping services and is well supported for importing to other systems.
- Adds compatible export for iShare system and the Coalition of the Willing.
- Is built for expandability by a custom plugin. [A starter plugin is include in this code base](https://github.com/DiscipleTools/disciple-tools-metrics-export/tree/master/plugin-extension-template) and [a guide is included in documentation for a developer](https://github.com/DiscipleTools/disciple-tools-metrics-export/wiki/Developer-Guide) to expand and add exports appropriate to your organizations usage. 

#### Will Not Do

- Exports are preconfigured and cannot be modified by the user, only selected.
- Connect to cloud storage services (yet!)

## Requirements

- Disciple Tools Theme installed on a Wordpress Server.

## Installing

- Install as a standard Disciple.Tools/Wordpress plugin in the system Admin/Plugins area.
- Requires the user role of Administrator.

## Contribution

Contributions welcome. You can report issues and bugs in the
[Issues](https://github.com/DiscipleTools/disciple-tools-metrics-export/issues) section of the repo. You can present ideas
in the [Discussions](https://github.com/DiscipleTools/disciple-tools-metrics-export/discussions) section of the repo. And
code contributions are welcome using the [Pull Request](https://github.com/DiscipleTools/disciple-tools-metrics-export/pulls)
system for git. For a more details on contribution see the
[contribution guidelines](https://github.com/DiscipleTools/disciple-tools-metrics-export/blob/master/CONTRIBUTING.md).


## Screenshots

This plugin enables a Disciple Tools system to export telemetry data in a secure manner to visualization systems. The key to this plugin is that it is expandable to serve other kinds of exports beyond the default export.

![one time link](https://raw.githubusercontent.com/DiscipleTools/disciple-tools-metrics-export/master/documentation/metrics-export-one-time-link.png)

## Video Walkthrough

[![Alt text](https://img.youtube.com/vi/ylYhsEUYQwc/maxresdefault.jpg)](https://www.youtube.com/watch?v=ylYhsEUYQwc)
[View Video](https://www.youtube.com/watch?v=ylYhsEUYQwc)
