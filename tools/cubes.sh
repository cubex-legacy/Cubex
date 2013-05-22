rm -rf .subsplit/



git subsplit init git@github.com:qbex/Cubex
git subsplit publish src/Cubex/Type:git@github.com:qbex/type.git
git subsplit publish src/Cubex/Data:git@github.com:qbex/data.git
rm -rf .subsplit/



git subsplit init git@github.com:qbex/project
git subsplit publish bin:git@github.com:qbex/bin.git
rm -rf .subsplit/
