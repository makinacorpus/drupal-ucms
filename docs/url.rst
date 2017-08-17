# Generating URL

## Common case

### Usage

If you need to generate URL within any site:

 *  within Drupal code, just use the ```url()``` function;

 *  within Twig templates, use the ```path()``` twig function, which is
    profixied transparently to ```url()``` by the *sf_dic* module.


### Common behavior

Behaviour you should be aware of, when you are in a site:

 *  local allowed admin URLs will always be generated toward the current site;

 *  other (actually most of) admin URLs will always be generated toward the
    master site;

 *  any other URL will always be generated locally on the current site.

And when you are on the master:

 *  admin URLs will always be generated on the master site;

 *  content URLs will be generated on the most revelant local site if one
    if found where the user has access to.

Please note that as long as you build absolute URL by yourselves, all the
consistency checks are skipped. **It is important that in case you need to**
**generate and URL for a specific site, the above API is mandatory** otherwise
URL consistency (site allowed protocols or such) cannot be guaranted.

### Technical details

Main entry point for this is the ```ucms_site_url_outbound_alter()```
implementation, please see the code for a detailed algorithm.


## Generating an URL forcing the site

Forcing the site hostname for an URL can be useful is some edge cases:

 *  you wish to see a node in determined local site;

 *  you are generating a node canonical URL;

 *  you need to redirect the user on a form in a specific site context that
    will alter its behavior and allow the user a specific action.

Behaviour you should be aware of:

 *  per default, if you are already in the same site context than the target
    site, URL will be relative;

 *  on the contrary, if context differs, URL will be absolute;

 *  if you are logged in the URL will be generated with the target site Single-Sign-On
    entry point, and the target route will be added in the ``destination`` query
    parameter, it allows transparent login for the user accross sites, while
    default transparent URL alteration does not;

 *  if URL is absolute, HTTP host is handle by the platform and the current
    protocol (``http`` or ``https``) is not supported by the target site,
    protocol will be changed in the URL to the supported one.

 *  default rules of the default transparent link generation applies too.


### In PHP code

For this, you need the ```ucms_site.url_generator``` service.

```php
// Generating a random URL in a site:
$siteUrlGenerator = $container->get('ucms_site.url_generator');
$siteOrSiteId = some_way_to_fetch_it();

list($path, $options) = $siteUrlGenerator->getRouteAndParams(
    $siteOrSiteId,
    'my/route/to/generate',
    // Options are optionnal, and they are directly passed to the Drupal
    // url() function, without any further processing.
    [
        'absolute' => true,
        'some_other_options' => 42,
    ]
);

// You can then use:
$myURL = url($path, $options);
```

Additionnally, you can generate the string URL directly:

```php
// Generating a random URL in a site:
$siteUrlGenerator = $container->get('ucms_site.url_generator');
$siteOrSiteId = some_way_to_fetch_it();

$myURL = $siteUrlGenerator->generateUrl($siteOrSiteId, 'my/route/to/generate');
```

Please note that if you already have access to the manager instance, you don't
need the additional URL generator dependency by doing so:
```php
$siteManager = $container->get('ucms_site.manager');
$siteOrSiteId = some_way_to_fetch_it();

$myURL = $siteManager->getUrlGenerator()->generateUrl($siteOrSiteId, 'my/route/to/generate');
```


### In twig templates

You may use the ``ucms_site_url()`` twig function, which signature is the same
as the ``SiteUrlGenerator::generateUrl()`` method.

