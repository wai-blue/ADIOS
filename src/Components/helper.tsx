export function deepObjectMerge(target: any, source: any): Object {
  for (const key in source) {
    if (source.hasOwnProperty(key)) {
      if (source[key] instanceof Object && key in target) {
        deepObjectMerge(target[key], source[key]);
      } else if (source[key] != undefined) {
        target[key] = source[key];
      } else {
        target[key] = target[key];
      }
    }
  }

  return target;
}

