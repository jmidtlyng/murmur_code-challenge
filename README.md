# Murmur Code Challenge
This is Jacob Midtlyng's submission for Murmur's code challange with the following requirements:
>Create a composer package that can be installed locally using ~composer create-project~ And which uses autoloading. Attached is a CSV file containing image links. The package/program will need to run, ingest the CSV, and download all the images to a directory.
>
>This needs to run on PHP 7.3+ and should run on the command line, but does not need to generate useful output
>
>When you are done please send us a zipped git repository or public git(hub|bucket) link.

## Use
This code sample can be installed and run using the following steps:
1. Clone this repo _git clone git@github.com:jmidtlyng/murmur_code-challenge.git_
2. Run _composer create-project_ from the cloned project directory.
3. After composer creates the project, run _composer run-script download-images_ from the root of the project directory.

## Configuration
The input CSV and the output images accept simple configurations using the config.json file at src/config.json relative to project root.
Here is the default configuration
```
{
    "input": {
        "path": "input/images.csv",
        "extensionsAllowed": [
            "jpeg",
            "jpg",
            "png"
        ]
    },
    "output": {
        "images":
            {
                "directory": "output/images",
                "overwrite": 0
            }
    }
}
```

* input.path and output.images.directory are default to locations relative to running the _download-images_ script from directory root, but can be changed.
* output.images.overwrite determines accepts 1 or 0. 
    * If set to 1; downloaded images will overwrite images with the same name in the output path. 
    * If set to 0; downloaded images will be appended with a version number incrementing by 1 if there is an image with the same name. 
    * For example:
        * imagename.jpg
        * imagename--1.jpg
        * imagename--2.jpg
* input.extensionsAllowed is an array of accepted file extensions for image downloads. If the retreived file doesn't match one of these image types, it will not be downloaded and the warning will be logged.

### Errors and Warnings
Errors and warnings are logged at output/errors.log relative to root directory of project.
