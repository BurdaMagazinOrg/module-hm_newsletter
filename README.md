# Harbourmaster Newsletter integration

This modules provides integration wuth Harbourmaster Newsletter service.

### Configuration

After module is enabled following things has to be configured:
1. In administration page: `/admin/config/hm_newsletter/newsletter`, "Client ID" has to be set. Also displayed agreements should be checked are they in compliance to site.
2. After that Newsletter block should be added to wanted page. That can be done on `` page with proper configuration of block.
3. When block is placed it has to be configured and one of important configurations is "Newsletters" text. It has to be in following format:
```
<Client ID>_<Group ID>|<Text displayed next to checkbox>
```
for example:
```
100001_10|Weekly newsletter with latest published articles
100001_11|Monthly newsletter with most read articles
```
4. After that newsletter form should functioning properly.
