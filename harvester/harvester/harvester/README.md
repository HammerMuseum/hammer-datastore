# Harvesters

## Development

The `harvester` directory contains two useful files:

- `HarvesterBase.py`, a base class for creating harvesters
- `SimpleHarvester.py`, a demo of a minimal harvester class subclassing `HarvesterBase`

You'll be able to see from `HarvesterSimple` that creating a new harvester is very simple - the only abstract methods required are `validateRecord()` and `harvest()`. What you do in these methods is entirely at the discretion of the implementing harvester.

The `HarvesterBase` provides some convenience code for your subclass - primarily a central `logger` that adds a `FileHandler` and `StreamHandler`. You'll see from the `HarvesterSimple` invocation that you'll need to provide the log directory and filename as part of the instance setup:

```
myHarvester = HarvesterSimple()
myHarvester.addLogger('/var/log/eap_harvester', 'eap_harvester.log')
myHarvester.logger.info('This is a log message')
myHarvester.outputDirectory = harvestDir
myHarvester.harvest()
```

The `AssetBankHarvester` adds some extra logic for harvesting, and some extra reporting in the form of a summary report. This may be drawn back into the `HarvesterBase` class if it's useful for other harvesters.
