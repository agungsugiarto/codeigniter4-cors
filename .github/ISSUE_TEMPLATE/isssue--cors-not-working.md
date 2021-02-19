---
name: 'Isssue: CORS not working'
about: CORS requests are blocked, follow these steeps
title: ''
labels: ''
assignees: ''

---

**Before you start**

[ ] Update to the latest version by running `composer update agungsugiarto/codeigniter4-cors`
[ ] Make sure that Apache/nginx/etc are NOT also adding CORS headers

**Check your config**

[ ]  Double-check your config file with the version from the repo.
[ ]  Make sure the filter is added to your config filter.

**Make the request**

Open Chrome Devtools to see which requests are actually happening. Make sure you see the actual OPTIONS requests for POST/PUT/DELETE (see https://stackoverflow.com/questions/57410051/chrome-not-showing-options-requests-in-network-tab)

Please show the actual request + response headers as sent by the OPTIONS request and the POST request (when available)
