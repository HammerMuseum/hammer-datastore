<!-- Generate a new file using -->
<!-- sed -e "s/\Paginate listings/My story/" -e "s/\168787614/156128780/" -e "s/\paginate-listings-168787614/`git_current_branch`/g" template.md | tee "`git_current_branch`.md" -->

# Paginate listings

## Related documentation

## Pivotal Story

* [Paginate listings](https://www.pivotaltracker.com/story/show/168787614)

## Git branch

* [paginate-listings-168787614](https://github.com/HammerMuseum/hammer-datastore/tree/paginate-listings-168787614)

## Description

**As a user I want to see a limited number of results at a time so that I initially load only the most relevant search results**

## Requirements to test
Access to the testing environment.

## Test Plan
- Navigate to `/api/videos`
    - Is the response limited to 10 records?
    - Is there a `_links` key?
    - Does it have a `next` value of a query string e.g `?start=11`
- Navigate to `/api/videos[query]` where `[query]` is the value of the `_links/next` result.
    - Are you taken to a new page of results?
    - Is the page limited to 10 records?
    - Is there a list of relevant numbers in the result, e.g:
            
            total
            totalPages
            currentPage
    - Is there a `_links` key?
    - Does it contain both a `next` and a `previous` value?
- Navigate to the same URL but with the `previous` value in place of the current query string.
    - Are you taken back to the original 10 results?
- Continue to progress through the results in this way until you reach the end.
    - Is each page of results a new set of 10?
- Navigate back to the beginning.


- Repeat the above test steps with a search that yields more than 10 results.
