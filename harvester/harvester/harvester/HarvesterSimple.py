import os
from harvester.harvester import HarvesterBase


class HarvesterSimple(HarvesterBase):
    def validate_record(self, record):
        """For the basic implementation, we always return True"""
        return True

    def harvest(self):
        """Do harvesting here"""
        for i in range(1, 10):
            self.write_record(str(i), "%s.xml" % i)

        return True


myRecord = {}
harvestDir = "/var/harvester/HarvesterSimple"

if not os.path.exists(harvestDir):
    os.mkdir(harvestDir)

# Example usage
myHarvester = HarvesterSimple()
myHarvester.add_logger("/var/log/harvester", "harvester.log")
myHarvester.logger.info("This is a log message")
myHarvester.outputDirectory = harvestDir
myHarvester.harvest()
