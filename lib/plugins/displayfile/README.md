# Display File Dokuwiki Plugin

[![MIT License](https://svgshare.com/i/TRb.svg)](https://opensource.org/licenses/MIT)
[![DokuWiki Plugin](https://svgshare.com/i/TSa.svg)](https://www.dokuwiki.org/dokuwiki)
[![Plugin Home](https://svgshare.com/i/TRw.svg)](https://www.dokuwiki.org/plugin:displayfile)
[![Gitlab Repo](https://svgshare.com/i/TRR.svg)](https://gitlab.com/JayJeckel/displayfile)
[![Gitlab Issues](https://svgshare.com/i/TSw.svg)](https://gitlab.com/JayJeckel/displayfile/issues)
[![Gitlab Download](https://svgshare.com/i/TT5.svg)](https://gitlab.com/JayJeckel/displayfile/-/archive/master/displayfile-master.zip)

The Display File Plugin displays the content of a specified file on the local system using a `displayfile` element. Language-specific syntax highlighting is support using the default Dokuwiki mechanisms and several configuration options give control over what files can and can't be displayed.

## Installation

Search and install the plugin using the [Extension Manager](https://www.dokuwiki.org/plugin:extension) or install directly using the latest [download url](https://gitlab.com/JayJeckel/displayfile/-/archive/master/displayfile-master.zip), otherwise refer to [Plugins](https://www.dokuwiki.org/plugins) on how to install plugins manually.

## Usage

The plugin offers a single block element that expands into the content of the specified file. The element is self-closing and should not be used as an open/close pair.

| Element | Note |
|:-|:-|
| `<<display file LANG TARGET>>` ||
| `<displayfile LANG TARGET />` | DEPRECATED |

| Argument | Required | Description |
|:-|:-|:-|
| `LANG` | yes | The language of the content file. This is used by Dokuwiki's built-in syntax highlighting GeSHi library. To disable syntax highlighting, specify a dask (-) character for the `LANG` value. The supported `LANG` values are the same as those provided by Dokuwiki's `<code>` and `<file>` markup and can be found on the Dokuwiki syntax page: [Syntax Highlighting](https://www.dokuwiki.org/wiki:syntax#syntax_highlighting) |
| `TARGET` | yes | The specific part of a file path to the desired file on the local file system. This will be appended to the value of the plugin's `root_path` configuration option. The `TARGET` value can be enclosed in single or double quotes (' or "). The `TARGET` path part must be enclosed in quotes if it contains spaces. |

## Configuration Settings

The plugin provides several settings that can be modified through the [Configuration Manager](https://www.dokuwiki.org/config:manager).

| Setting | Default | Description |
|:-|:-|:-|
| `root_path` | empty | Specifies the root directory displayed file paths will evaluate relative to. An empty value effectively disables the plugin. |
| `deny_extensions` | 'sh' | Space-separated list of extensions that should be disallowed by the `displayfile` element. The deny list supersedes the allow list. An empty list means no extension is explicitly disallowed. |
| `allow_extensions` | 'txt php js css' | Space-separated list of extensions that should be allowed by the `displayfile` element. An empty list means any extension not in the deny list will be allowed. |

## Security

Some level of threat is inherent in the very purpose of this plugin, displaying the contents of files from the local file system. To avoid path traversal attacks, the admin is provided with a configuration option for specifying the root directory path where displayable files are located. Validation is done to ensure that no files outside that root path are displayed and, further more, user-facing error messages have been generalized to remove the chance of ambient data probing. In addition, both allow and deny list configuration options exist to further control what files are and aren't displayable. Any security concerns or suggestions are welcome and should be raised on the [Issue Tracker](https://gitlab.com/JayJeckel/displayfile/issues).
