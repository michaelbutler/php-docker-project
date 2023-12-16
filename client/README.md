# PHP Sample Project ./client

This folder is the source for all javascript and scss. Everything gets compiled to
`./public/dist` so it can be served on the web.

## Images

Images that are referenced in CSS or JS should go in `public/source_assets/images` which will get auto copied to a file hash version
under `public/dist/images`. If the files aren't referenced anywhere, or you do not want this versioning feature, just store them
directly in `/public/images` or other folder inside `public/`. The resulting URL would just be `yourdomain.com/images/whatever.png`.

## Building

In dev, use `npm run build-dev` and for prod use `npm run build`. Be sure to commit the `public/dist` folder so it can
get copied into the docker image properly before deployment. However, the simplest way to go is just to run these `make` commands
in the root of the project:

`make watch` to auto compile on file changes while developing, and

`make assets` to run a build for production and then commit the result.
