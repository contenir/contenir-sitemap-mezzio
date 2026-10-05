# robots.txt

`Handler\RobotsHandler` answers with `200` and
`Content-Type: text/plain; charset=utf-8`:

```text
# robots.txt for https://www.example.com/

User-agent: *
Allow: /
Disallow: /.well-known/

Sitemap: https://www.example.com/sitemap.xml
```

1. A comment naming the site's [base URL](configuration.md#base_url).
2. One group per user agent, in configuration order: its `Allow` lines,
   then its `Disallow` lines. A group with neither is written as
   `Disallow:` (nothing disallowed).
3. `Sitemap:` with the base URL and the sitemap route's path, unless the
   sitemap route is disabled.

The file ends with a newline.

## Rules

`sitemap.robots.rules` maps user agents to their paths. Both lists are
optional:

```php
'sitemap' => [
    'robots' => [
        'rules' => [
            '*'         => ['allow' => ['/'], 'disallow' => ['/.well-known/', '/admin/']],
            'Googlebot' => ['disallow' => ['/search/']],
            'GPTBot'    => ['disallow' => ['/']],
            'Bingbot'   => [],
        ],
    ],
],
```

Configured rules replace the default rules
(`RobotsRules::DEFAULT_RULES`, the laminas-mvc module's), so repeat
`'/.well-known/'` if you still want it disallowed. `'rules' => []` writes
only the comment and the Sitemap line.

Each value is checked when the handler is built, and an invalid one throws
an `InvalidArgumentException`:

- User agents are non-empty string keys. A list of groups (integer keys)
  is rejected.
- A group is an array with only `allow` and `disallow` keys, each a list of
  paths. A misspelt key such as `dissallow` is rejected rather than
  ignored.
- User agents and paths may not be empty or contain a line break or other
  control character, so a value cannot add directives of its own.

## Building the rules in code

`RobotsRules` and `RobotsGroup` can be built directly, for a robots handler
of your own:

```php
use Contenir\Sitemap\Mezzio\Robots\RobotsGroup;
use Contenir\Sitemap\Mezzio\Robots\RobotsRules;

$rules = new RobotsRules([
    new RobotsGroup('*', allow: ['/'], disallow: ['/admin/']),
]);

$body = $rules->render('https://www.example.com', 'https://www.example.com/sitemap.xml');
```
