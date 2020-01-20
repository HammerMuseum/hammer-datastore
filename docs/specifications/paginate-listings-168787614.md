<!-- Generate a new file using -->
<!-- sed -e "s/\Paginate listings/My story/" -e "s/\168787614/156128780/" -e "s/\paginate-listings-168787614/`git_current_branch`/g" spec-template.md | tee "`git_current_branch`.md" -->

# Paginate listings

A specification for paginating API responses.

## Pivotal story

[Paginate listings](https://www.pivotaltracker.com/story/show/168787614)

## Git branch

[paginate-listings-168787614](https://github.com/HammerMuseum/hammer-datastore/paginate-listings-168787614)

## Story description

**As a user I want to see a limited number of results at a time so that I initially load only the most relevant search results**

**Developer notes:**
- Laravel documentation for paginating API responses (if needed): https://laravel.com/docs/6.x/pagination

---
**Acceptance criteria**
- Does a search or or other GET from the API return 10 results with a URL query string in the response for navigating to the next page of results?
- Does page 2 of a listing  return 10 results with the query string to go to the previous page? 
- Is the total number of results returned in the response?

## Implementation
- Pass a `size` option into the default Elasticsearch parameters.
- Construct the 'next page' query string from the results and pass back in the response (if there is one)
- Construct the 'previous page' query string from the results (if there is one)

## Documentation required
Explain next/prev page query string and how to use.
