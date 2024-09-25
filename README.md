## **\# PDI**

### Módulo e tema PDI carrinho ajax Magento 2

##   
**Automated Setup (New Project)**

#### Create your project directory then go into it:
https://github.com/markshust/docker-magento?tab=readme-ov-file#setup

```plaintext
mkdir -p ~/Sites/magento
cd $_
```

###   
Run this automated one-liner from the directory you want to install your project.

```plaintext
curl -s https://raw.githubusercontent.com/markshust/docker-magento/master/lib/onelinesetup | bash -s -- magento.test 2.4.7 enterprise
```

###   
Install sample data

```plaintext
bin/magento sampledata:deploy
bin/magento setup:upgrade
```

###   
Copy the "code" and "design" folders contained within this "pdi" repo into the "app" folder
