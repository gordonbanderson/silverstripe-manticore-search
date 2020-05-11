source {$IndexName}_source
\{
	type			= mysql

	## SQL settings (for 'mysql' and 'pgsql' types)
	sql_host		= $DB_HOST
	sql_user		= $DB_USER
	sql_pass		= $DB_PASSWD
	sql_db			= $DB_NAME
	sql_port		= 3306

	sql_query_pre		= SET NAMES utf8
	sql_query_pre		= SET SESSION query_cache_type=OFF


	sql_query		= $SQL

    #First argument is always the ID for Sphinx
	#sql_attr_uint		= ID

    #@todo These need aliased
    <% loop $Attributes %>$Type = $Name
    <% end_loop %>
\}


index {$IndexName}_index
{
	# index type;
	# optional, default is 'plain'
	# known values are 'plain', 'distributed', and 'rt' (see samples below);
	# type			= plain

	# document IDs must be globally unique across all sources
	source			= {$IndexName}_source

	# index files path and file name, without extension
	# mandatory, path must be writable, extensions will be auto-appended
	path			= /var/lib/sphinxsearch/data/{$IndexName}

	# document attribute values (docinfo) storage mode
	# optional, default is 'extern'
	# known values are 'none', 'extern' and 'inline'
	docinfo			= extern

	# dictionary type, 'crc' or 'keywords'
	# crc is faster to index when no substring/wildcards searches are needed
	# crc with substrings might be faster to search but is much slower to index
	# (because all substrings are pre-extracted as individual keywords)
	# keywords is much faster to index with substrings, and index is much (3-10x) smaller
	# keywords supports wildcards, crc does not, and never will
	# optional, default is 'keywords'
	dict			= keywords

	# memory locking for cached data (.spa and .spi), to prevent swapping
	# optional, default is 0 (do not mlock)
	# requires searchd to be run from root
	mlock			= 0

	# See http://sphinxsearch.com/docs/current/conf-morphology.html for alternative languages
	morphology		= stem_en

#This enables suggest CALL QSUGGEST('cyclin','flickr_index');
#See also https://docs.manticoresearch.com/latest/html/sphinxql_reference/call_qsuggest_syntax.html
min_word_len = 3
charset_type = utf-8
enable_star = 1
min_infix_len=2

    html_strip = 1
}

# realtime index example
#
# you can run INSERT, REPLACE, and DELETE on this index on the fly
# using MySQL protocol (see 'listen' directive below)

#@todo Update this appropriately

index {$IndexName}_rt
{
	# 'rt' index type must be specified to use RT index;
	type			= rt

	# index files path and file name, without extension
	# mandatory, path must be writable, extensions will be auto-appended
	path			= /var/lib/sphinxsearch/data/{$IndexName}_rt

	# RAM chunk size limit
	# RT index will keep at most this much data in RAM, then flush to disk
	# optional, default is 128M
	#
	# rt_mem_limit		= 128M

	# full-text field declaration
	# multi-value, mandatory
	rt_field		= title
	rt_field		= content

	# unsigned integer attribute declaration
	# multi-value (an arbitrary number of attributes is allowed), optional
	# declares an unsigned 32-bit attribute
	rt_attr_uint		= gid

	# RT indexes currently support the following attribute types:
	# uint, bigint, float, timestamp, string, mva, mva64, json
	#
	# rt_attr_bigint		= guid
	# rt_attr_float		= gpa
	# rt_attr_timestamp	= ts_added
	# rt_attr_string		= author
	# rt_attr_multi		= tags
	# rt_attr_multi_64	= tags64
	# rt_attr_json		= extra_data

    dict=keywords
min_word_len = 3
charset_type = utf-8
enable_star = 1
min_infix_len=2
}
