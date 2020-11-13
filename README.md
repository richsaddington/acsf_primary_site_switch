# About/What does it do?

A simple script that does the following:
- Adds sites to a site collection (optional)
- Sets a site to be the primary
- Polls the site factory to check when the swith is complete
- Reports back when swith is complete and the time taken

# Install/setup

Install dependencies with composer
```composer init; composer require guzzlehttp/guzzle```

1. Populate the $config arrays

2. Set the ACSF site IDs for the site collection

3. Set the site id to make the primary site

# Usage

Execute with ./acsf_set_primary_site.php
 
# Support/Help

This script is designed to provide an example of the ACSF API calls and should be used to inform an API implementation.
