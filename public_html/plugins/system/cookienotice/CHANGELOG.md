# Changelog

## 1.0.19
- Added Joomla-compatible `changelog.xml`
- Added manifest `changelogurl` support
- Added explicit `<element>cookienotice</element>` in the manifest
- Added `CHANGELOG.md` for repository/release notes
- No runtime code changes; based on the JED-clean 1.0.18 release

## 1.0.18
- Fixed accidental `\n` output in the banner markup
- Cleaned HTML output generation
- Reduced CSS dependence on Bootstrap utility classes
- Hardened button and link styling inside plugin CSS

## 1.0.17
- Added missing **Title** plugin parameter to match runtime behavior
- Refactored plugin to Joomla 6-style `SubscriberInterface` event handlers
- Limited processing to **site HTML documents** only
- Added `joomla.asset.json` and registered assets through Web Asset Manager
- Improved standalone CSS for non-Bootstrap templates
- Added secure cookie flag on HTTPS requests
- Improved translated default title and close-label handling
