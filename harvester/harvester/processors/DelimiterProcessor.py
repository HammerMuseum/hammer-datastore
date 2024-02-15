class DelimiterProcessor:
    """
    A basic processor to split field values based on delimiters.

    The core process() method accepts a dictionary representing a CSV row. It will split configured
    fields on configured delimiters, replacing the field values for the split fields with lists.

    This delimiter expects a scalar value for the input field value.

    The processor will also remove any empty items from split list.
    """

    def __init__(self, harvester, fields=[], delimiter=";"):
        self.harvester = harvester
        self.fields = fields
        self.delimiter = delimiter

    def process(self, row):
        for field in self.fields:
            self.harvester.logger.debug("Processing field {!s}".format(field))

            try:
                if self.delimiter in row[field]:
                    row[field] = [v.strip() for v in row[field].split(self.delimiter)]
                    row[field] = [v for v in row[field] if v]
            except KeyError:
                self.harvester.logger.warning(
                    "Field {!s} not found in row".format(field)
                )
            except AttributeError:
                self.harvester.logger.warning(
                    "Unable to split field {!s} of type {!s}".format(
                        field, type(row[field]).__name__
                    )
                )
