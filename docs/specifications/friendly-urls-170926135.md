<!-- Generate a new file using -->
<!-- sed -e "s/\Friendly urls/My story/" -e "s/\170926135/156128780/" -e "s/\friendly-urls-170926135/`git_current_branch`/g" spec-template.md | tee "`git_current_branch`.md" -->

# Friendly urls

This is a template for a specification.

## Pivotal story

[Friendly urls](https://www.pivotaltracker.com/story/show/170926135)

## Git branch

[friendly-urls-170926135](https://github.com/HammerMuseum/hammer-datastore/friendly-urls-170926135)

## Story description

Add plugin to the data adaptor to convert titles of extracted videos into URL slugs.

**Acceptance criteria:**

- Is an appropriate URL generated for each video from the video title?
- Are appropriate unsafe characters removed from all generated URLs?
- Are all slugs unique?

## Implementation

- Add a new processor to the Harvester that takes a field and creates a urlsafe version of the field value
- Create or use an existing open source slugify implementation to generate the value
- To ensure that there are no duplicate url slugs, some validation will need to happen, most likely in the harvester as this is where the slugs are generated. Most likely that the asset discovered to have a non-unique slug will be classed as failing validation and the error logged with a suitable message.