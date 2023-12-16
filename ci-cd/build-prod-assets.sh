#!/bin/bash

set -ex

echo "$(date) Building production assets..."

# Run NPM build... this outputs to public/dist/ (HTML index file, JS, CSS)
npm run build

# Run PHP post script to extract hashes from index file
php ./ci-cd/update-prod-assets.php

git add config/asset_config.php
git add --force public/dist
git commit -m "Automated asset compilation"

echo "$(date) Committed production assets to the branch."
