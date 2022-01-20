# Jbuncle PHP Standards

Custom PHP CheckStyle (PHPCS) RuleSet and additional Sniffs for my own personal projects.

*Note*: this defines a full set of rules I have selected for myself plus the ruleset for loading the custom sniffs
i.e. it doesn't just for loading in the additional Sniffs.
(in hindsight they should have been seperated, but it was convenient at the time).


## How it works

- Defines ruleset.xml in the project root for phpcs to find, this also defines the name of the standard as `php-standards`
- ruleset.xml loads in 3rd party sniffs and the bespoke sniffs
- Uses `dealerdirect/phpcodesniffer-composer-installer` composer plugin to install the standard 
  (which installs the standard under the defined name in the ruleset.xml)

## Usages

Install with composer

Run the checks with `phpcs` (on the desired directory)

`vendor/bin/phpcs -n -s --standard=php-standards ./src`

*Note*: in hindsight I wouldn't have names the custom standards as `php-standards` - probably `jbuncle-php-standards` instead

## Bespoke Sniffs

### Commenting Sniffs

*JBuncle.Commenting.ClassComment* - Check if the class has a doc comment (and that isn't a  specific generic template message from NetBeans)
*JBuncle.Commenting.FileComment* - Ensures the file doc comment has a 1 of 2 specific copyright headers
*JBuncle.Commenting.FileCommentSpacing* - Ensures the file doc comment doesn't have double spacings
*JBuncle.Commenting.FileCommentStart* - Ensures the file doc comment starts with `/**` style opening comment

### Code Sniffs

*JBuncle.CodeErrors.ClassHasConstructor* - Ensure class has a constructor defined (has fixer)
*JBuncle.CodeErrors.ClassNameMatchesFileName* - Ensire the class name matches the filename - should normally be used alongside OneClassPerFile sniff
*JBuncle.CodeErrors.MemberInitialisation* - Ensures that every class member is initialised in the constructor
*JBuncle.CodeErrors.OneClassPerFile* - Ensures that there is only one class per PHP file