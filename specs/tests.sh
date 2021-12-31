curl --location --request DELETE 'http://familyfund.leadconcept.business/api/assets/5'
curl --location --request GET 'http://familyfund.leadconcept.business/api/assets'
curl --location --request DELETE 'http://familyfund.leadconcept.business/api/assets/7'
curl --location --request GET 'http://familyfund.leadconcept.business/api/assets'
curl --location --request GET 'http://familyfund.leadconcept.business/api/assets/1'
 
alias ASSET_CREATE="/usr/bin/curl --location --request POST 'http://familyfund.leadconcept.business/api/assets' "
ASSET_CREATE --data-raw '{"name" : "sp500a", "type" : "stock", "feed_id" : "ctv", "source_feed" : "aws", "last_price": 0}'
ASSET_CREATE --data-raw '{"name" : "sp500a", "type" : "stock", "feed_id" : "ctv", "source_feed" : "aws"}'
ASSET_CREATE --data-raw '{"name" : "sp500a", "type" : "stock", "feed_id" : "ctv", "last_price": 0}'
ASSET_CREATE --data-raw '{"name" : "sp500a", "type" : "stock", "source_feed" : "aws", "last_price": 0}'
ASSET_CREATE --data-raw '{"name" : "sp500a", "feed_id" : "ctv", "source_feed" : "aws", "last_price": 0}'
ASSET_CREATE --data-raw '{"type" : "stock", "feed_id" : "ctv", "source_feed" : "aws", "last_price": 0}'

 
