# Пример из реального текущего проекта по документообороту в организации
- Контроллер: для организации работы со входящими документами и реализующий основные CRUD и вспомогательные операции (DocumentInController)
- Модели DocumentIn (автогенерируемый через Gii) и DocumentInWork (наследуемый и расширяющий функционал класса DocumentIn)
- Компоненты: трейт FileInteraction, реализующий основные методы работы с файлами
- Сервисы: сервис DocumentInService, реализующий генерацию зависимого выпадающего списка
