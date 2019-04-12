# TeamDeployment
Hot deployment stuff!

###Console
To install activate and update all plugins, use\
`bin/console team-deployment:deploy`

If you want to start the interactive mode, use\
`bin/console team-deployment:deploy -i`

###API
POST /api/v{version}/_action/plugin/deploy\
POST /api/v{version}/_action/plugin/install\
POST /api/v{version}/_action/plugin/activate\
POST /api/v{version}/_action/plugin/update\
POST /api/v{version}/_action/plugin/deactivate

##Contributors
Lucas Herbst @lherbst\
Amir El Sayed @Schwierig\
Patrick Kumm @patkdot