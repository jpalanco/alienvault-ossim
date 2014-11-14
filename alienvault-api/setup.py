from setuptools import setup, find_packages

# This is the distribute flavour of setuptools.
# See:
#   http://packages.python.org/distribute/
#   http://guide.python-distribute.org/
#   http://flask.pocoo.org/docs/patterns/distribute/#distribute-deployment

setup(
    name='Alienvault REST API',
    version='0.1',
    description='Alienvault Rest API',
    long_description=file('README.rst').read(),
    author='Alienvault Development Team',
    author_email='devel@alienvault.com',
    maintainer='Alienvault Development Team',
    maintainer_email='devel@alienvault.com',
    license=file('LICENSE').read(),
    #url='http://bitbucket.org/tcorbettclark/rest-api-blueprint/',
    platforms='any',

    # See http://pypi.python.org/pypi?%3Aaction=list_classifiers
    classifiers=[
        'Environment :: Web Environment',
        'Intended Audience :: Developers',
        'License :: OSI Approved :: BSD License'
        'Operating System :: OS Independent',
        'Programming Language :: Python',
        'Topic :: Internet :: WWW/HTTP',
        'Topic :: Software Development :: Libraries :: Python Modules'
    ],

    # Read package data files from MANIFEST.in
    # (no need for a package_data directive).
    include_package_data=True,

    # Automatically find Python packages.
    packages=find_packages(),

    # Do not allow to be installed as zip archive.
    zip_safe=False,

    # Required packages.
    # See http://justcramer.com/2012/04/24/sticking-with-standards/
    install_requires=[line.strip() for line in file('requirements.txt')],

    # Look for own packages here. Simply an http directory listing of correctly
    # named tarballs.
    ## dependency_links=['http://...']
)
