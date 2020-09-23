"""
A setuptools based setup module.
See:
https://packaging.python.org/en/latest/distributing.html
https://github.com/pypa/sampleproject
"""

# Always prefer setuptools over distutils
from setuptools import setup

# To use a consistent encoding
from os import path

here = path.abspath(path.dirname(__file__))

setup(
    name="harvester",
    # Versions should comply with PEP440.  For a discussion on single-sourcing
    # the version across setup.py and the project code, see
    # https://packaging.python.org/en/latest/single_source_version.html
    version="0.1",
    description="Asset harvester",
    # You can just specify the packages manually here if your project is
    # simple. Or you can use find_packages().
    packages=["harvester"],
    # List run-time dependencies here.  These will be installed by pip when
    # your project is installed. For an analysis of "install_requires" vs pip's
    # requirements files see:
    # https://packaging.python.org/en/latest/requirements.html
    install_requires=[
        "pyyaml",
        "lxml",
        "pytz",
        "pytest",
        "tqdm",
        "dateparser",
        "elasticsearch",
        "python-dotenv",
        "requests",
    ],
)
