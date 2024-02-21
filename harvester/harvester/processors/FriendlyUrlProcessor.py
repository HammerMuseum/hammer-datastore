import re
import unicodedata


class FriendlyUrlProcessor:
    """
    A processor to duplicate a field into a url friendly version.

    Makes a best attempt to create a slug from unicode strings.

    Adds a new row containing the slugified URL string.

    Based on Django's `slugify` (BSD License).
    https://docs.djangoproject.com/en/3.0/_modules/django/utils/text/#slugify
    """

    def __init__(self, harvester, fields=[]):
        self.harvester = harvester
        self.fields = fields

    def slugify(self, text):
        value = (
            unicodedata.normalize("NFKD", str(text))
            .encode("ascii", "ignore")
            .decode("ascii")
        )
        value = re.sub(r"[^\w\s-]", "", value).strip().lower()
        return re.sub(r"[-\s]+", "-", value)

    def process(self, row):
        for field in self.fields:
            try:
                self.harvester.logger.debug("Processing a {!s}".format(field))
                new_field = "{}_slug".format(field)
                text = row[field]
                row[new_field] = self.slugify(text)
            except Exception as e:
                self.harvester.logger.error(e)
