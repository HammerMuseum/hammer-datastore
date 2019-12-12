from lxml import etree, isoschematron


class Validator:
    """
    Validation class to apply schematron validation to XML documents. Takes a bytes string and returns True/False.

    This class has one property and two methods:
    - schema, a property which creates a schematron etree schema. Set using a path to the schema file.
    - validate(), a method which validates a supplied record using the schema.
    - validation_errors(), a method which provides a nicely formatted list of validation errors from the raw XML report.

    The exceptions which should be handled from a Validator are:
    - ValidatorSchemaError, thrown from the schema setter when there are issues creating the schema
    - ValidatorValidationError, thrown from validate() or validation_errors() when there are issues mapping the record
      using the transform or processing the validation errors list.

    These are both subclasses of ValidatorError.
    """

    _schema = None

    last_report = None

    last_valid = None

    namespaces = {
        'svrl': 'http://purl.oclc.org/dsdl/svrl',
    }

    @property
    def schema(self):
        return self._schema

    @schema.setter
    def schema(self, schema_path: str):
        """
        Creates an etree schema from an absolute path to a schema file.
        """
        try:
            self._schema = etree.parse(schema_path)
        except Exception as e:
            raise ValidatorSchemaError('Error creating schema from path {!s}: {!s}'.format(schema_path, e))

    def validate(self, record):
        """
        Validates the record against the internal schema.
        """
        try:
            document = etree.fromstring(record)
            schematron = isoschematron.Schematron(self._schema, store_report=True)
            self.last_valid = schematron.validate(document)
            self.last_report = schematron.validation_report
            return self.last_valid
        except Exception as e:
            raise ValidatorValidationError('Error validating the record against the schema: {!s}'.format(e))

    def validation_errors(self):
        """
        Extract an array of validation errors from the raw XML report.
        """
        try:
            errors = []

            if self.last_report is None:
                return errors

            for element in self.last_report.xpath('/svrl:schematron-output/*', namespaces=self.namespaces):
                if element.xpath('local-name()') == 'fired-rule':
                    rule_context = element.xpath('@context')
                    next_element = element.getnext()

                    if next_element is not None and next_element.xpath('local-name()') == 'failed-assert':
                        schematron_error = element.getnext().getchildren()[0].xpath('text()')
                        error = '{!s}. Error context: <{!s}> element'.format(schematron_error[0], rule_context)
                        errors.append(error)

            return errors
        except Exception as e:
            raise ValidatorValidationError('Error processing validation errors: {!s}'.format(e))


class PassValidator(Validator):
    """
    Stub validator, which mimics the core Validator but always returns valid.
    """
    @property
    def schema(self):
        return None

    @schema.setter
    def schema(self, schema_path: str):
        pass

    def validate(self, record):
        return True

    def validation_errors(self):
        return []


class ValidatorError(Exception):
    """Generic base class for validation exceptions."""
    pass


class ValidatorSchemaError(ValidatorError):
    """Exception to be used as a catch-all for validation schema issues."""
    pass


class ValidatorValidationError(ValidatorError):
    """Exception to be used as a catch-all for validation issues."""
    pass
