 <h1 class="sr-only">$Title</h1>

<div class="section section-first">
  <div class="container">
    <div class="g-row jfy-center">
      <div class="g g-11@xl g-9@xxl">
        <% include SearchForm Class=fs-40 fw-bold ff-alt lh-1, SearchForm=$SearchForm(SearchPage, $SearchTerm) %>
        <hr class="bt-ash">

        <div class="pt-s1 fs-20 fs-21@sm">
          <%t Reg\Search\SearchResultsPage.SearchResults '{count} Resultate' count=$SearchResults.Count %>
        </div>
      </div>
    </div>
  </div>
</div>
<hr class="bt-ash">

<div class="section section-last">
  <div class="container">
    <div class="g-row jfy-center">
      <div class="g g-11@xl g-9@xxl">
        <% if $SearchResults.Exists %>
          <% loop $SearchResults %>
            <div class="bb-1 bb-solid bb-ash<% if $First %> pb-s1 pb-s1.25<% else %> py-s1 py-s1.25<% end_if %>">
              <a class="d-flx ts-c ts-200 c-primary:hover group" href="$Record.Link">
                <div class="d-none d-blk@sm flx-none pr-s1 pr-1.5@sm pr-s2@md fs-28 fw-bold lh-1.1 ff-alt fs-32@md mb-s0.6 mb-s1@sm">
                  {$Up.CalculatePos($Pos)}.
                </div>

                <div class="flx-auto w-100% maxw-800">
                  <h2 class="fs-28 fw-bold lh-1.1 ff-alt fs-32@md mb-s0.6 mb-s1@sm">
                    <span class="d-ibl pr-s0.3 d-none@sm">
                      {$Up.CalculatePos($Pos)}.
                    </span>
                    $Title.RAW.ContextSummary(200)
                  </h2>

                  <p class="mb-s0.6 mb-s1@sm fs-18@md">
                    $SearchableText.RAW.ContextSummary(200)
                  </p>

                  <p class="d-flx ai-center fw-500 truncate">
                    $Record.Breadcrumbs(20, true)
                    <%-- $Record.Link --%>
                  </p>
                </div>

                <div class="d-none d-blk@sm flx-none ml-s1 ml-auto@md mr-s1@sm o-0 o-1:group-hover">
                  <% include Icon Icon=arr-rgt, Class=fl-txt %>
                </div>
              </a>
            </div>
          <% end_loop %>

          <% include Pagination List=$SearchResults, Class=pt-s1.5 %>
        <% else %>
          <div class="d-blk -mb-1">
            <%t Reg\Search\SearchResultsPage.NoResults 'Bitte überprüfen Sie die Schreibweise oder versuchen Sie es erneut mit einem anderen Begriff.' %>
          </div>
        <% end_if %>
      </div>
    </div>
  </div>
</div>