<h2>Example page results</h2>
<% if $Records %>
    <h2>Results for &quot;{$Query}&quot;</h2>
    <p>Displaying Page 1 of 4</p>
    <ol>
        <% loop $Records %>
            <li>
                <h3><a href="$Link">$Title</a></h3>
                <p><% if $Abstract %>$Abstract.XML<% else %>$Content.ContextSummary<% end_if %></p>
            </li>
        <% end_loop %>
    </ol>
<% else %>
    <p>Sorry, your search query did not return any results.</p>
<% end_if %>
