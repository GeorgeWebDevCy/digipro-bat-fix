# ACF Tab Extractor & Cleaner

This repository contains a small WordPress utility plugin that migrates tabbed content from the `mec-events` post type into an Advanced Custom Fields (ACF) repeater field. The plugin also cleans the original post content so only the introductory text remains.

## Files

- **tab-extractor.php** – WordPress plugin that performs the extraction and cleanup.
- **acf-export-2025-06-28.json** – Export of the ACF field group used by the plugin. Import this via **Custom Fields → Tools**.
- **Instructions for a specific exaample** – Example HTML showing the sort of content the script expects to process.

## Installing

1. Copy `tab-extractor.php` to your site's `wp-content/plugins/` directory.
2. In the WordPress admin, go to **Plugins** and activate **ACF Tab Extractor & Cleaner for Events**.
3. Import `acf-export-2025-06-28.json` using **Custom Fields → Tools** so the `tabs` repeater is available.

## Running the extractor

After activation a new page will appear under **Tools → ACF Tab Extractor**. Visit this page and click **"Extract & Clean Tabs Now"**. The script loops through all posts of the `mec-events` type, looking for heading lines. It records text from `<a>` tags and then matches those names to plain text headings later in the content. Each heading and its content become a row in the ACF repeater field named `tabs`:

```php
update_field('tabs', $tabs, $post->ID);
```

All lines from the original post starting at the list of tab names are removed, leaving just the introductory portion intact. Progress messages are displayed for each processed post.

## ACF field structure

The exported field group defines a repeater `tabs` with two sub fields, `tab_heading` (text) and `tab_content` (WYSIWYG editor). It is assigned to the `mec-events` post type.

```json
{
    "key": "field_6829bb34b8f7f",
    "label": "Tabs",
    "name": "tabs",
    "type": "repeater",
    "sub_fields": [
        { "name": "tab_heading", "type": "text" },
        { "name": "tab_content", "type": "wysiwyg" }
    ]
}
```

## Customisation

If your site uses a different post type or field names, update the values in `tab-extractor.php`. The script currently queries `mec-events` and writes to the `tabs` repeater.

## Sample content

The file `Instructions for a specific exaample` illustrates HTML where a short list of `<a>` elements names each tab (e.g. `<a>Program</a>` or `<a>Audience</a>`). The same names appear again as plain text headings before their content. This format is representative of what the extractor parses into ACF repeater rows.

## License

This project is provided as-is with no warranty. Modify and use it in your WordPress projects as needed.
