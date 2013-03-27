echo "Updating Bin Repo"
git subsplit init git@github.com:qbex/project
git subsplit publish bin:git@github.com:qbex/bin.git
rm -rf .subsplit/

#echo "Updating Foundation"
#git subsplit init git@github.com:qbex/Cubex
#git subsplit publish src/Cubex/Foundation:git@github.com:qbex/Foundation.git
#rm -rf .subsplit/
