const fs = require('fs');

const vuexyPkg = JSON.parse(fs.readFileSync('D:/Development/Theme and templates/vuexy-admin-v10.11.1/nuxt-version/typescript-version/starter-kit/package.json', 'utf8'));
const targetPkg = JSON.parse(fs.readFileSync('./package.json', 'utf8'));

// Merge dependencies
targetPkg.dependencies = { ...vuexyPkg.dependencies, ...targetPkg.dependencies };
targetPkg.devDependencies = { ...vuexyPkg.devDependencies, ...targetPkg.devDependencies };
targetPkg.resolutions = vuexyPkg.resolutions;
targetPkg.overrides = vuexyPkg.overrides;

fs.writeFileSync('./package.json', JSON.stringify(targetPkg, null, 2));

console.log('Successfully merged package.json');
