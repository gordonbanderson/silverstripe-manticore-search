http://sphinxsearch.com/forum/view.html?id=9540
Fields are full-text indexed - suitable for searching

Attributes are stored as is - and so useful for sorting/grouping and even getting the
information out.

http://sphinxsearch.com/forum/view.html?id=5440
> ... UNIX_TIMESTAMP(Document.dateFiled) as dateFiled, ...
Converts it to a unix timestamp - Good!

> sql_attr_timestamp = dateFiled
Makes it an attribute - also Good!

http://sphinxsearch.com/docs/current/conf-sql-attr-timestamp.html
Note that DATE or DATETIME column types in MySQL can not be directly used as timestamp attributes in Sphinx; you need to explicitly convert such columns using UNIX_TIMESTAMP function (if data is in range). 

http://sphinxsearch.com/docs/current/conf-sql-attr-string.html
string not indexed - token?

http://sphinxsearch.com/docs/current/conf-sql-field-string.html - this allows full text search

MVA
http://sphinxsearch.com/docs/current/conf-sql-attr-multi.html
http://sphinxsearch.com/forum/view.html?id=9158
 sql_attr_multi = uint categories from query; SELECT entry_id, cat_id FROM exp_category_posts
 


