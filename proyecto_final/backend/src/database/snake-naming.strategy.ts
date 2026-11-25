import { DefaultNamingStrategy, NamingStrategyInterface } from 'typeorm';

const aSnake = (s: string) =>
  s
    .replace(/([a-z0-9])([A-Z])/g, '$1_$2')
    .replace(/([A-Z])([A-Z][a-z])/g, '$1_$2')
    .toLowerCase();

/**
 * Convierte los nombres de propiedades TypeScript (camelCase) a columnas
 * SQL en snake_case, p. ej. `fechaIngreso` → `fecha_ingreso`.
 */
export class SnakeNamingStrategy
  extends DefaultNamingStrategy
  implements NamingStrategyInterface
{
  override tableName(className: string, customName?: string): string {
    return customName ?? aSnake(className);
  }

  override columnName(
    propertyName: string,
    customName: string | undefined,
    embeddedPrefixes: string[],
  ): string {
    return aSnake(
      embeddedPrefixes.concat(customName ?? propertyName).join('_'),
    );
  }

  override relationName(propertyName: string): string {
    return aSnake(propertyName);
  }

  override joinColumnName(
    relationName: string,
    referencedColumnName: string,
  ): string {
    return aSnake(`${relationName}_${referencedColumnName}`);
  }

  override joinTableName(
    firstTableName: string,
    secondTableName: string,
  ): string {
    return aSnake(`${firstTableName}_${secondTableName}`);
  }

  override joinTableColumnName(
    tableName: string,
    propertyName: string,
    columnName?: string,
  ): string {
    return aSnake(`${tableName}_${columnName ?? propertyName}`);
  }
}
