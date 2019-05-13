# Paddix

## Description
Manage entity synchrinization with Paddix instance.


## Requirements
* [Node](https://www.drupal.org/docs/8/core/modules/node)
* [File](https://www.drupal.org/docs/8/core/modules/file)


## Configuration
You can configure global config from _"/admin/config/services/paddix"_


## Mapping
Add key `mapping` to custom `paddix.settings.yml`, for example:

```yaml
mapping:
  edition:
    class: \Drupal\node\Entity\Node
    type: liseuse
    adapters:
      - service: paddix.edition_adapter
        edition: nid
        fields:
          title: title
        defaults:
          template: edition
          status: published
      - service: paddix.flatplan_adapter
        edition: nid
        flatplan: nid
        fields:
          title: title
          data:
            liseuse_file: field_pdf
        defaults:
          template: flatplan
```
